<?php

declare(strict_types=1);

// app/Models/ApiUser.php
namespace App\Models\LowCode;

use Illuminate\Contracts\Auth\Authenticatable;

class ApiAccount implements Authenticatable
{
    protected $attributes = [];

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getAuthPassword()
    {
        return null;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // 不需要实现
    }

    public function getRememberTokenName()
    {
        return null;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }
}