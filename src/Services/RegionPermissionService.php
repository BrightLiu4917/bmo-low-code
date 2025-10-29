<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Support\Facades\Log;

/**
 * @Class
 * @Description:权限服务
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class RegionPermissionService extends LowCodeBaseService
{
    use WithContext;

    /**
     * 管理区域字段映射
     *
     * @return array
     */
    public static function manageAreaFieldMap(): array
    {
        return [
            'prv'  => 'curr_addr_prv_cd',
            'cty'  => 'curr_addr_cty_cd',
            'cnty' => 'curr_addr_cnty_cd',
            'twn'  => 'curr_addr_twn_cd',
            'vlg'  => 'curr_addr_vlg_cd',
        ];
    }

    /**
     * 格式化权限条件
     *
     * @return array
     */
    public function formatPermission(?array $manageAreaCodes = null): array
    {
        try {
            $manageAreaCodes = $manageAreaCodes ?? $this->getManageAreaCodes();

            if (empty($manageAreaCodes)) {
                return [];
            }

            $conditions = $this->buildRegionConditions($manageAreaCodes);

            return empty($conditions) ? [] : [['raw', "({$conditions})"]];

        } catch (\Exception $exception) {
            Logger::REGION_PERMISSION_ERROR->error('权限条件格式化错误', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);

            return [];
        }
    }

    /**
     * 构建区域条件SQL
     *
     * @param array $manageAreaCodes
     * @return string
     */
    private function buildRegionConditions(array $manageAreaCodes): string
    {
        $fieldMap = self::manageAreaFieldMap();
        $symbolic = config('low-code.region-permission-symbolic-condition', 'or');

        $conditions = [];

        foreach ($fieldMap as $regionLevel => $field) {
            $codes = $manageAreaCodes[$regionLevel] ?? [];

            if (!empty($codes)) {
                $inClause = $this->buildInClause($codes);
                $conditions[] = "{$field} IN ({$inClause})";
            }
        }

        return implode(" {$symbolic} ", $conditions);
    }

    /**
     * 构建IN条件子句
     *
     * @param array $codes
     * @return string
     */
    private function buildInClause(array $codes): string
    {
        // 安全过滤：确保所有代码都是数字且长度为12
        $filteredCodes = array_filter($codes, function($code) {
            return is_string($code) && preg_match('/^\d{12}$/', $code);
        });

        if (empty($filteredCodes)) {
            return '';
        }

        $quotedCodes = array_map(function($code) {
            return "'" . addslashes($code) . "'";
        }, $filteredCodes);

        return implode(',', $quotedCodes);
    }

    /**
     * 使用Laravel查询构建器的安全版本
     *
     * @return array
     */
    public function formatPermissionSafe(): array
    {
        try {
            $manageAreaCodes = $this->getManageAreaCodes();

            if (empty($manageAreaCodes)) {
                return [];
            }

            return $this->buildSafeConditions($manageAreaCodes);

        } catch (\Exception $exception) {
            Logger::REGION_PERMISSION_ERROR->error('权限条件构建错误', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);

            return [];
        }
    }

    /**
     * 构建安全的查询条件
     *
     * @param array $manageAreaCodes
     * @return array
     */
    private function buildSafeConditions(array $manageAreaCodes): array
    {
        $fieldMap = self::manageAreaFieldMap();
        $hasConditions = false;

        // 使用闭包构建条件
        $condition = function ($query) use ($manageAreaCodes, $fieldMap, &$hasConditions) {
            $symbolic = config('low-code.region-permission-symbolic-condition', 'or');

            $query->where(function ($q) use ($manageAreaCodes, $fieldMap, $symbolic, &$hasConditions) {
                foreach ($fieldMap as $regionLevel => $field) {
                    $codes = $manageAreaCodes[$regionLevel] ?? [];

                    if (!empty($codes)) {
                        $hasConditions = true;

                        if ($symbolic === 'or') {
                            $q->orWhereIn($field, $codes);
                        } else {
                            $q->whereIn($field, $codes);
                        }
                    }
                }
            });
        };

        return $hasConditions ? [$condition] : [];
    }
}