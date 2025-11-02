<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Models\DataPermission;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Support\Collection;

/**
 * @Class DataPermissionService
 * @Description: 数据权限服务 - 负责权限数据的格式化和处理
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class DataPermissionService extends LowCodeBaseService
{
    use WithContext;

    protected string $channel = '';
    protected string $permissionKey = '';

    // 渠道处理器映射
    private const CHANNEL_HANDLERS = [
        'region' => 'handleRegionPermission',
        'org'    => 'handleOrgPermission',
    ];
    protected array $mappingField = [];


    /**
     * 查询所有权限数据
     */
    public static function getAllPermission()
    {
        return DataPermission::getAllData();
    }

    /**
     * 设置数据权限渠道
     */
    public function channel(?string $channel = null): static
    {
        $this->channel = $channel ?? config('low_code.use-data-permission', '');
        return $this;
    }

    /**
     * 设置权限键
     */
    public function setPermissionKey(string $permissionKey = ''): static
    {
        $this->permissionKey = $permissionKey;
        return $this;
    }

    /**
     * 格式化权限数据为键值对
     */
    public function formatPermissionData(): array
    {
        $permissions = self::getAllPermission();

        if ($permissions === null) {
            return collect();
        }

        return $permissions->keyBy('code')->toArray();
    }

    public function setMappingField(array $values = []):static
    {
        $this->mappingField = $values;
        return  $this;
    }

    /**
     * 执行权限处理流程
     */
    public function run(): array
    {
        try {
            // 参数验证
            if (empty($this->channel)) {
                throw new \InvalidArgumentException('Data permission channel is required');
            }

            // 获取权限配置
            $permissionConfig = $this->getPermissionConfig();
            if (empty($permissionConfig)) {
                return [];
            }

            // 处理多字段情况
            if ($this->isMultipleField($permissionConfig)) {
                return $this->processMappingField($this->getChannelPermissionValue());
            }

            // 处理单字段权限
            return $this->processMappingField($this->formatSingleFieldPermission($permissionConfig));
        }catch (\Throwable $exception){
            Logger::DATA_PERMISSION_ERROR->error($exception);
        }
        return  [];
    }


    /**
     * 处理字段映射
     */
    public function processMappingField($data)
    {
            if (empty($this->mappingField)) {
                return $data;
            }

            // 将数据转换为JSON字符串
            $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($jsonString === false) {
                return $data;
            }

            // 准备替换映射
            $search  = [];
            $replace = [];

            foreach ($this->mappingField as $field => $mapping) {
                // 构造JSON键名格式进行精确替换
                $search[]  = $field;
                $replace[] = $mapping;
            }

            // 执行字符串替换
            $mappedJson = str_replace($search, $replace, $jsonString);

            // 解码回数组
            $result = json_decode($mappedJson, true);

            return $result !== null ? $result : $data;

    }
    /**
     * 获取当前渠道的权限配置
     */
    private function getPermissionConfig()
    {
        $permissions = $this->formatPermissionData();
        return $permissions[$this->channel] ?? [];
    }

    /**
     * 检查是否为多字段配置
     */
    private function isMultipleField(array $permissionConfig): bool
    {
        $permissionKey = $permissionConfig['permission_key'] ?? '';
        $operationSymbol = $permissionConfig['operation_symbol'] ?? '';

        return $permissionKey === 'multiple_field' || $operationSymbol === 'multiple_field';
    }

    /**
     * 获取渠道权限值
     */
    private function getChannelPermissionValue(): array
    {
        $handlerMethod = self::CHANNEL_HANDLERS[$this->channel] ?? null;

        if ($handlerMethod && method_exists($this, $handlerMethod)) {
            return $this->{$handlerMethod}();
        }

        // 默认处理器
        return match ($this->channel) {
            'region' => $this->handleRegionPermission(),
            'org'    => $this->handleOrgPermission(),
            default  => $this->handleUnknownChannel(),
        };
    }

    /**
     * 处理区域权限
     */
    private function handleRegionPermission(): array
    {
        return RegionPermissionService::instance()->formatPermission();
    }

    /**
     * 处理组织权限
     */
    private function handleOrgPermission(): array
    {
        return OrgPermissionService::instance()->formatOrg();
    }

    /**
     * 处理未知渠道
     */
    private function handleUnknownChannel(): array
    {
        // 记录日志或抛出异常，根据业务需求决定
        \Log::warning("Unknown data permission channel: {$this->channel}");
        return [];
    }

    /**
     * 格式化单字段权限
     */
    private function formatSingleFieldPermission(array $permissionConfig): array
    {
        $permissionKey = $permissionConfig['permission_key'] ?? '';
        $operationSymbol = $permissionConfig['operation_symbol'] ?? '';
        $permissionValue = $this->getChannelPermissionValue();

        // 验证必要字段
        if (empty($permissionKey) || empty($operationSymbol)) {
            \Log::warning("Invalid permission configuration", [
                'channel' => $this->channel,
                'config' => $permissionConfig
            ]);
            return [];
        }

        return [[$permissionKey, $operationSymbol, $permissionValue]];
    }

    /**
     * 批量处理多个渠道的权限
     */
    public function runMultiple(array $channels): array
    {
        $results = [];

        foreach ($channels as $channel) {
            try {
                $results[$channel] = $this->channel($channel)->run();
            } catch (\Exception $e) {
                \Log::error("Failed to process permission channel: {$channel}", [
                    'exception' => $e->getMessage()
                ]);
                $results[$channel] = [];
            }
        }

        return $results;
    }

    /**
     * 获取可用的渠道列表
     */
    public function getAvailableChannels(): array
    {
        return array_keys(self::CHANNEL_HANDLERS);
    }

    /**
     * 验证渠道是否支持
     */
    public function isChannelSupported(string $channel): bool
    {
        return array_key_exists($channel, self::CHANNEL_HANDLERS);
    }

    /**
     * 获取当前配置信息（用于调试）
     */
    public function getConfigInfo(): array
    {
        return [
            'current_channel' => $this->channel,
            'permission_key' => $this->permissionKey,
            'available_channels' => $this->getAvailableChannels(),
            'is_channel_supported' => $this->isChannelSupported($this->channel),
        ];
    }
}