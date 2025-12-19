<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Enums\Model\AdminPreference\SceneEnum;
use BrightLiu\LowCode\Models\AdminPreference;
use Gupo\BetterLaravel\Service\BaseService;

final class AdminPreferenceService extends BaseService
{
    /**
     * 处理 列表列配置，结合用户偏好设置.
     */
    public function handleColumnConfig(string $listCode, array $columnConfig): array
    {
        try {
            $preference = AdminPreference::query()
                ->where('scene', SceneEnum::LIST_COLUMNS)
                ->where('pkey', $listCode)
                ->value('pvalue');

            // 获取预设配置
            $presetConfig = array_column($columnConfig, null, 'key');

            // 优先使用用户偏好设置，结合预设配置中的自定义选项
            if (!empty($preference)) {
                $columnConfig = array_map(
                    fn ($item) => $presetConfig[$item['column']] ?? [
                        'title' => $item['name'],
                        'key' => $item['column'],
                    ],
                    $preference
                );
            }
        } catch (\Throwable) {
        }

        return $columnConfig;
    }
}
