<?php

namespace BrightLiu\LowCode\Imports;

interface ImportCodeInterface
{
    /**
     * 获取处理器唯一标识
     * @return string
     */
    public static function getImportCode(): string;
}
