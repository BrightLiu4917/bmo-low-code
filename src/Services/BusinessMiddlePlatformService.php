<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;

use Throwable;
use Illuminate\Support\Facades\Http;
use App\Models\LowCode\DatabaseSource;
use Illuminate\Http\Client\RequestException;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use App\Services\LowCode\LowCodeQueryEngineService;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;


/**
 * @Class
 * @Description: 业务中台调用服务
 * @created    : 2025-10-18 17:15:59
 * @modifier   : 2025-10-18 17:15:59
 */
class BusinessMiddlePlatformService extends LowCodeBaseService
{
    use WithOrgContext, WithDiseaseContext, WithAuthContext;

    protected string $orgCode = '';
    protected string $systemCode = '';
    protected string $databaseName = '';
    protected string $diseaseCode = '';
    protected string $residentUniqueIndexValue = '';

    // 配置缓存
    protected ?string $cachedDatabase = null;

    public function baseUrl(): string
    {
        return config('low-code.business_middle_platform.base_uri', '');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function whereUserId(string $value = ''): self
    {
        $this->residentUniqueIndexValue = $value;
        return $this;
    }

    /** 必须调用方法 如果不入参 就会使用默认参数
     * @param string|null $residentUniqueIndexValue
     * @param string|null $orgCode
     * @param string|null $systemCode
     * @param string|null $databaseName
     * @param string|null $diseaseCode
     *
     * @return $this
     */
    public function setMustArgs(
        ?string $orgCode = null,
        ?string $systemCode = null,
        ?string $databaseName = null,
        ?string $diseaseCode = null,
    ): static {
        $this->orgCode                  = $orgCode ?? $this->getOrgCode();
        $this->systemCode               = $systemCode ?? $this->getSystemCode();
        $this->databaseName             = $databaseName ??
            $this->resolveDatabaseName($diseaseCode);
        $this->diseaseCode              = $diseaseCode ??
            $this->getDiseaseCode();
        return $this;
    }

    /**
     * 保存到业务中台
     *
     * @param array       $args
     * @param string|null $residentUniqueIndex
     * @param string|null $orgCode
     * @param string|null $systemCode
     * @param string|null $databaseName
     * @param string|null $diseaseCode
     *
     * @return array
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function updateResidentData(
        array $args = [],
    ): array {
        try {
            $payload      = $this->preparePayload(
                $args,
            );
            $responseData = Http::asJson()
                                ->timeout(3)
                                ->post($this->baseUrl().
                                    '/innerapi/personal-archive/create',
                                    $payload)
                                ->throw()
                                ->json();
            return $responseData;
        } catch (RequestException $e) {

            Logger::BUSINESS_MIDDLE_PLATFORM_ERROR->error('业务中台请求失败', [
                'url'     => $this->baseUrl(),
                'error'   => $e->getMessage(),
                'payload' => $payload ?? null,
            ]);
            throw $e;
        } catch (Throwable $e) {
            Logger::WIDTH_TABLE_DATA_RESIDENT->error('保存居民数据时发生意外错误',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            throw $e;
        }
    }

    /**
     * 准备请求负载
     */
    protected function preparePayload(
        array $args,
    ): array {
        if (empty($this->residentUniqueIndexValue)) {
            throw new \Exception('请设置居民唯一索引值');
        }
        $residentUniqueIndexField
            = config('low-code.business_middle_platform.resident_unique_index_field',
            'user_id');

        $latestSaveArgs = [
            'data_source'             => 1,
            'db_name'                 => $this->databaseName,
            'disease_code'            => $this->diseaseCode,
            'operator_id'             => auth()->user()->id ?? 0,
            'operator_name'           => auth()->user()->name ?? '',
            'org_code'                => $this->orgCode,
            'sys_code'                => $this->systemCode,
            'col_values'              => $this->formatColumnValues($args),
        ];
        if ($this->residentUniqueIndexValue != '') {
            $index = ($residentUniqueIndexField ?? 'user_id');
            $latestSaveArgs[$index] = $this->residentUniqueIndexValue;
        }
        return $latestSaveArgs;
    }

    /**
     * 解析数据库名称
     *
     * @param string|null $diseaseCode
     *
     * @return string|null
     */
    public function resolveDatabaseName(?string $diseaseCode): ?string
    {
        $diseaseCode = $diseaseCode ?? $this->getDiseaseCode();
        if ($this->cachedDatabase === null) {
            $this->cachedDatabase
                = DatabaseSource::fetchDataByField(filterField: 'disease_code',
                value: $diseaseCode, columns: ['database']);
        }

        return $this->cachedDatabase;
    }

    /**
     * 格式化列值数据 java入参的奇怪格式
     */
    public function formatColumnValues(array $data): array
    {
        return empty($data)
            ? []
            : array_map(
                fn ($key, $value) => [
                    'col_name' => $key, 'col_value' => $value,
                ],
                array_keys($data),
                $data
            );
    }

    /** 纳管患者
     * @param array $data
     *
     * @return array
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function manageResident(array $args)
    {
        $user = auth()->user();

        // 只处理绝对必要的字段
        $defaults = [
            'manage_status' => $args['manage_status'] ?? 1,
            'manage_start_at' => $args['manage_start_at'] ?? now()->format('Y-m-d H:i:s'),
            'manage_org_code' => $args['manage_org_code'] ?? $this->orgCode,
            'manage_doctor_code' => $args['manage_doctor_code'] ?? $user->account ?? '',
            'manage_doctor_name' => $args['manage_doctor_name'] ?? $user->name ?? '',
            'manage_end_at'      => null, //有些患者 会再次纳管 默认将出组时间null
        ];

        // 合并数据
        $fullPayload = array_merge($args, $defaults);

        return $this->updateResidentData($fullPayload);
        /**
         * 纳管状态           manage_status         int      // 状态标识（0:待纳管 1:已纳管 2:拒绝纳管 3:退出纳管）
         * 纳管机构编码       manage_org_code         string   // 机构唯一标识
         * 纳管机构名称       manage_org_name         string   // 机构全称
         * 主管医生编码       manage_doctor_code     string   // 医生唯一标识
         * 主管医生姓名       manage_doctor_name     string   // 医生姓名
         * 纳管生效时间       manage_start_at         time     // 纳管操作时间
         * 纳管团队编码       manage_team_code      string   // 团队唯一标识
         * 纳管团队名称       manage_team_name      string   // 团队全称
         * 纳管科室编码       manage_dept_code      string   // 科室唯一标识
         * 纳管科室名称       manage_dept_name      string   // 科室全称
         * 纳管终止时间       manage_end_at          time     // 取消纳管时间（含退出/出组/死亡）
         */
    }

    /** 出组患者
     * @param array $args
     * @param bool  $isMoveMangeInfo
     *
     * @return array
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function removeManagetResident(array $args = [],bool $isMoveMangeInfo = true)
    {
        $movedArgs = [];
            $args['manage_end_at'] ?? now()->format('Y-m-d H:i:s');
        $args['manage_status'] = 3;
        if ($isMoveMangeInfo){
            $movedArgs = $this->moveManageInfo();
        }
        return $this->updateResidentData(array_merge($args,$movedArgs));
    }

    /** 删除纳管相关信息
     * @return array
     */
    public function moveManageInfo():array
    {
        return [
            'manage_start_at' => null,
            'manage_org_code' => '',
            'manage_org_name' => '',
            'manage_doctor_code' => '',
            'manage_doctor_name' => '',
            'manage_team_code' => '',
            'manage_team_name' => '',
            'manage_dept_code' => '',
            'manage_dept_name' => '',
        ];
    }


    /** 废弃
     * @param array $args
     *
     * @return array|mixed|void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function createResident(array $args)
    {
        if (empty($args)) {
            return;
        }

        $args['user_id'] = md5($args['id_crd_no']);

        return Http::asJson()->timeout(3)
                   ->post($this->baseUrl().'innerapi/personal-crowd/create',[
                       'personal_batch_list' => [
                           ['col_values' => $this->formatColumnValues($args)],
                       ],
                       'data_source'  => 1,
                       'org_code'     => $this->orgCode,
                       'sys_code'     => $this->systemCode,
                       'disease_code' => $this->diseaseCode,
                   ])->throw()->json();
    }

    /**
     * 获取患者人群分类
     * @param array $userIds
     *
     * @return mixed
     */
    public function getPatientCrowds(array $userIds = [])
    {
        return QueryEngineService::instance()
                                 ->autoClient()
                                 ->useTable(config('low-code.bmo-business-center.crowd-type-table'))
                                 ->setCache(50)
//                                 ->whereDiseaseCode($this->diseaseCode)
                                 ->whereBatchUserIds($userIds)
                                 ->select(['user_id', 'group_id', 'group_name'])
                                 ->getAllResult();
    }
}
