<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Traits\Context;

use BrightLiu\LowCode\Context\AdminContext;
use Illuminate\Support\Arr;

trait WithAdminContext
{
    protected ?array $contextAdmin = null;

    public function byAdmin(array $adminInfo): static
    {
        $this->contextAdmin = $adminInfo;

        return $this;
    }

    public function getAdmin(): ?array
    {
        if (empty($this->contextAdmin)) {
            $this->contextAdmin = AdminContext::instance()->getAdminInfo();
        }

        return $this->contextAdmin;
    }

    public function getAdminId(): int
    {
        return (int) Arr::get($this->getAdmin(), 'id', 0);
    }

    public function getAdminName(): string
    {
        return (string) Arr::get($this->getAdmin(), 'name', '');
    }

    public function getAdminPhone(): string
    {
        return (string) Arr::get($this->getAdmin(), 'phone', '');
    }
}
