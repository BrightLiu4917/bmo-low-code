<?php

namespace BrightLiu\LowCode\Exceptions;

use Exception;

class QueryEngineException extends Exception
{
    /**
     * 构造函数
     *
     * @param string $message 异常信息，默认为 "低代码查询引擎异常"
     * @param int $code 异常代码，默认为 500
     * @param Exception|null $previous 前一个异常（如果有）
     */
    public function __construct(string $message = '', int $code = 500, Exception $previous = null)
    {
        parent::__construct("查询引擎异常:".$message, $code, $previous);
    }
}
