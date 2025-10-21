<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Traits\Context;

use BrightLiu\LowCode\Context\AuthContext;

trait WithAuthContext
{
    protected ?array $auth = null;

    public function byAuth(array $auth): static
    {
        $this->auth = $auth;

        return $this;
    }

    public function getAuth(?string $key = null): mixed
    {
        if (empty($this->auth)) {
            $authContext = AuthContext::instance();

            $this->auth = [
                'token' => $authContext->getToken(),
                'org_id' => $authContext->getOrgId(),
                'system_code' => $authContext->getSystemCode(),
            ];
        }

        return is_null($key) ? $this->auth : ($this->auth[$key] ?? null);
    }

    public function getToken(): string
    {
        return (string) $this->getAuth('token');
    }

    public function getOrgId(): int
    {
        return (int) $this->getAuth('org_id');
    }

    public function getSystemCode(): string
    {
        return (string) $this->getAuth('system_code');
    }

    public function getTenantId(): string
    {
        return (string) config('business.bmp-service.custom.tenant_id', 0);
    }
}
