<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;


/**
 * @Class
 * @Description:签名
 * @created: 2025-11-14 14:04:28
 * @modifier: 2025-11-14 14:04:28
 */
final class Signature
{
    /**
     * 生成签名
     * @param $appKey
     * @param $appSecret
     * @param $datetime
     * @param $nonce
     * @param $data
     *
     * @return string
     */
    public static function generateSignature($appKey, $appSecret, $datetime, $nonce, $data = [ ]):string

    {
        // 按照字母表顺序排序请求参数
        ksort($data);

        // 生成请求体的哈希值
        $hashBody = hash("sha256", http_build_query($data));

        // 拼接临时签名字符串
        $tempSign = sprintf("%s:%s:%s:%s", $appKey, $datetime, $nonce, $hashBody);

        // 使用HMAC-SHA256算法和应用密钥生成签名
        return hash_hmac("sha256", $tempSign, $appSecret);
    }

}
