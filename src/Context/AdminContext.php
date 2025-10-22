<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Context;

/**
 * 管理员上下文
 */
class AdminContext
{
    /**
     * 用户中心 api/apiUser/apiUserDetails数据
     */
    protected array $info = [
        'id' => 'admin',
        'name' => '管理员',
        'phone' => '',
    ];

    public static function instance(): static
    {
        return app('context:admin');
    }

    public static function init(array $info): static
    {
        return tap(
            static::instance(),
            function (AdminContext $context) use ($info) {
                $context->setAdminInfo($info);
            }
        );
    }

    public function setAdminInfo(array $info): void
    {
        $this->info = $info;
    }

    public function getAdminInfo(): array
    {
        return $this->info;
    }

    public function getAdminId(): string
    {
        return $this->info['id'] ?? '';
    }

    public function getAdminName(): string
    {
        return $this->info['name'] ?? '';
    }

    public function setAdminId(string $value): void
    {
        $this->info['id'] = $value;
    }

    public function setAdminName(string $value): void
    {
        $this->info['name'] = $value;
    }
}
