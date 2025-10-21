<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Requests\Foundation\DatabaseSourceRequest;

use Gupo\BetterLaravel\Validation\BaseRequest;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

/**
 * 基础-数据源
 */
final class DatabaseSourceRequest extends BaseRequest
{
    use ValidatorScenes;

    /**
     * @return array
     */
    public function rules(): array
    {
        return ['id'          => ['bail', 'required', 'numeric'],
                'name'        => ['bail', 'required', 'string', 'max:32'],
                'username'    => ['bail', 'nullable', 'string', 'max:32'],
                'password'    => ['bail', 'required', 'string', 'max:32'],
                'option'      => ['bail', 'nullable','array'],
                'host'        => ['bail', 'required', 'max:64'],
                'database'    => ['bail', 'required', 'max:64'],
                'table'       => ['bail', 'required', 'max:64'],
                'source_type' => ['bail', 'numeric', 'in:1,2'],
                'port'        => ['bail', 'required', 'numeric'],];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return ['id'          => 'ID', 'name' => '名称', 'port' => '端口号',
                'table'       => '表名', 'username' => '账号',
                'password'    => '密码', 'source_type' => '数据源类型',
                'host'        => '主机地址', 'database' => '数据库'];
    }

    /**
     * @return array
     */
    public function scenes(): array
    {
        return ['create' => ['name', 'port', 'table', 'username', 'password',
                             'source_type', 'host', 'database'],
                'update' => ['name', 'port', 'table', 'username', 'password',
                             'source_type', 'host', 'database', 'id'],
                'delete' => ['id',], 'show' => ['id',]];
    }
}
