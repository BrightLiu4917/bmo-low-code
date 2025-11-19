<?php

namespace BrightLiu\LowCode\Exports;

use App\Http\Resources\QueryResource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource;

class LowCodeExport
    implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $originalData;
    protected $processedData;
    protected $headings;
    protected $fieldOrder;
    protected $resourceClass;

    public function __construct($data, $headings = [], $fieldOrder = [])
    {
        $this->resourceClass = config('low-code.dependencies.BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource');

        $this->originalData = $data;
        $this->headings     = $headings;
        $this->fieldOrder   = $fieldOrder;

        // 使用 QueryResource 处理数据
        $this->processedData = $this->processWithQueryResource();
    }

    /**
     * 表头
     */
    public function headings(): array
    {
        if (empty($this->headings)) {
            return [];
        }

        // 处理不同类型的表头格式
        if (isset($this->headings[0]) && is_array($this->headings[0])) {
            // 格式: [['title' => '标题1'], ['title' => '标题2']]
            return array_column($this->headings, 'title');
        } else {
            // 格式: ['标题1', '标题2']
            return $this->headings;
        }
    }

    public function collection()
    {
        return collect($this->processedData);
    }

    /**
     * 数据映射 - 修复可能的问题
     */
    public function map($row): array
    {
        $rowArray = (array)$row;

        // 如果提供了自定义表头结构，按照表头的field顺序输出
        if (isset($this->headings[0]) && is_array($this->headings[0])) {
            $mappedData = [];
            foreach ($this->headings as $heading) {
                $field = $heading['key'];
                $fieldVariant = $field.'.variant';

                $value = $rowArray[$fieldVariant] ?? $rowArray[$field] ?? '';
                if (is_numeric($value) && strlen($value) > 10) {
                    $value = $this->formatAsText($value);
                }
                $mappedData[] = $value;
            }
            return $mappedData;
        }

        // 否则按原顺序输出
        return array_values($rowArray);
    }

    /**
     * 样式设置 - 取消注释并修复
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // 表头样式
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color'    => ['argb' => 'FFE6E6FA'],
                ],
            ],
        ];
    }

    /**
     * 设置列宽（可选）
     */
    public function columnWidths(): array
    {
        // 设置每列宽度为自动
        $widths        = [];
        $headingsCount = count($this->headings());

        for ($i = 0; $i < $headingsCount; $i++) {
            $column
                             = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                $i + 1
            );
            $widths[$column] = 50; // 或者设置为自动：'auto'
        }

        return $widths;
    }

    protected function processWithQueryResource()
    {
        if (empty($this->originalData)) {
            return [];
        }

        $resourcesKey = config('low-code.dependencies.BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource');

        $data         = is_array($this->originalData) ?
            collect($this->originalData) : $this->originalData;
        $resourceData = $this->resourceClass::collection($data);

        return $resourceData->toArray(request());
    }

    /**
     * 格式化值为文本
     */
    protected function formatAsText($value)
    {
        return "\t".(string)$value;
    }
}