<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Core\DbConnectionManager;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Imports\ResidentMaintenance\ResidentImport;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Services\LowCode\DatabaseSourceService;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 居民维护相关
 * // TODO: 写法待完善。。。
 */
final class ResidentMaintenanceService extends BaseService
{
    use WithContext;

    /**
     * 创建
     *
     * @throws ServiceException
     */
    public function create(array $data): void
    {
        if (empty($data['id_crd_no'])) {
            throw new ServiceException('参数错误');
        }

        $query = $this->query($connection = $this->connection());

        if (empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            throw new ServiceException('配置有误');
        }

        // 从宽表中获取empi
        $empi = $connection->table($psnTable)
            ->where('id_crd_no', $data['id_crd_no'])
            ->value('empi');

        if (!empty($empi) && $query->where('empi', $empi)->exists()) {
            throw new ServiceException('居民(身份证号)已存在');
        }

        try {
            // TODO: 写法待完善(需要过滤掉不存在的字段)
            BmpCheetahMedicalCrowdkitApiService::make()->createPatients([$data]);
        } catch (\Throwable $e) {
            Logger::LARAVEL->error('导入失败：' . $e->getMessage());
            throw new ServiceException('数据格式不正确');
        }
    }

    /**
     * 导入
     *
     * @throws ServiceException
     */
    public function import(UploadedFile $file): void
    {
        /** @var ResidentImport $handler */
        $handler = tap(new ResidentImport(), fn ($handler) => Excel::import($handler, $file));

        if (empty($data = $handler->getResult())) {
            return;
        }

        if (empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            throw new ServiceException('配置有误');
        }

        $query = $this->query($connection = $this->connection());

        // 从宽表中获取empi
        $empis = $connection->table($psnTable)
            ->whereIn('id_crd_no', $data->pluck('id_crd_no'))
            ->pluck('empi');

        if ($empis->isNotEmpty() && $query->whereIn('empi', $empis->toArray())->exists()) {
            throw new ServiceException('部分居民(身份证号)已存在');
        }

        try {
            // TODO: 写法待完善(需要过滤掉不存在的字段)
            $crowdSrv = BmpCheetahMedicalCrowdkitApiService::make();

            $data->chunk(100)->each(function ($data) use ($crowdSrv) {
                $crowdSrv->createPatients($data->toArray());
            });
        } catch (\Throwable $e) {
            Logger::LARAVEL->error('导入失败：' . $e->getMessage());
            throw new ServiceException('数据格式不正确');
        }
    }

    /**
     * 构建导入模板文件
     *
     * @throws ServiceException
     */
    public function buildImportTemplateFile(string $outputPath = '', bool $force = false): string
    {
        if (empty($outputPath)) {
            $outputPath = storage_path(
                sprintf('app/import_template_%s_%s.xlsx', $this->getDiseaseCode(), now()->format('Y_m_d_H'))
            );
        }

        if (!$force && file_exists($outputPath)) {
            return $outputPath;
        }

        $connection = DbConnectionManager::getInstance()->getConnection(
            DatabaseSourceService::instance()->getDataByDiseaseCode($this->getDiseaseCode())
        );

        if (empty($table = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            throw new ServiceException('解析源表失败');
        }

        // 获取列信息(排序内部字段)
        $columns = $connection->select("SHOW FULL COLUMNS FROM {$table}");

        // 指定排序优先级
        $priorityFields = ['id_crd_no', 'rsdnt_nm', 'gdr_cd', 'slf_tel_no', 'bth_dt', 'curr_addr'];
        $schema = collect($columns)
            ->map(fn ($column) => ['field' => $column->Field, 'name' => $column->Comment])
            ->filter(fn ($item) => !in_array($item['field'], ['user_id', 'is_deleted', 'gmt_created', 'gmt_modified']))
            ->sortBy(function ($item) use ($priorityFields) {
                $index = array_search($item['field'], $priorityFields, true);

                return false !== $index ? $index - count($priorityFields) : $item['field'];
            })
            ->values()
            ->all();

        // 初始化Excel模板
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 初始化表头
        foreach ($schema as $index => $col) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $col['name']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '2', $col['field']);
        }

        // 隐藏第二行
        $sheet->getRowDimension(2)->setVisible(false);

        // 保存文件
        (new Xlsx($spreadsheet))->save($outputPath);

        return $outputPath;
    }

    protected function connection(): Connection
    {
        return DbConnectionManager::getInstance()
            ->getConnection(DatabaseSourceService::instance()->getDataByDiseaseCode($this->getDiseaseCode()));
    }

    protected function query(?Connection $connection = null): Builder
    {
        $connection ??= $this->connection();

        return $connection->table($connection->getConfig('table'));
    }
}
