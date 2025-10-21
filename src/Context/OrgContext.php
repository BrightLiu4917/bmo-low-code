<?php


namespace BrightLiu\LowCode\Context;

use App\Enums\Foundation\Cacheable;
use BrightLiu\LowCode\Services\BmoAuthApiService;
use App\Services\Util\DynamicSearch\OrgSearch;
use Illuminate\Support\Arr;
use Gupo\BetterLaravel\Exceptions\ServiceException;

/**
 * 机构上下文
 */
final class OrgContext
{
    /**
     * @var string
     */
    protected string $orgCode = '';

    /**
     * @return static
     */
    public static function instance(): static
    {
        return app('context:org');
    }

    /**
     * @param string $orgCode
     *
     * @return static
     */
    public static function init(string $orgCode): static
    {
        return tap(
            static::instance(),
            function (OrgContext $context) use ($orgCode) {
                $context->setOrgCode($orgCode);
            }
        );
    }

    /**
     * 根据机构ID初始化
     *
     * @param int $orgId
     *
     * @return static
     */
    public static function initByOrgId(int $orgId): static
    {
        // TODO: 写法待完善
        // TODO: 当未解析到所在机构的编码时，特殊情况待处理
        $orgCode = Cacheable::USER_CENTER_ORG_CODE->remember(
            (string) $orgId,
            ttl: 60 * 60 * 24,
            callback: function () use ($orgId) {
                $hid = (string) Arr::get(BmoAuthApiService::instance()->getOrgDetail($orgId), 'hid', '');
                //非生产环境 如果hid是空获取配置的hid
                if (config('app.env','local') !== 'production') {
                    $hid = empty($hid) ? config('business.develop.authing_hid_id') : $hid;

                    //生产环境下的hid 空直接抛出异常
                } elseif (empty($hid)) {
                    Cacheable::USER_CENTER_ORG_CODE->delete((string)$orgId); // 清空缓存
                    throw new ServiceException("医院id未找到");
                }

                //获取机构编码
                $orgCode = rescue(fn () => OrgSearch::instance()->searchAppointIdColumnValueByHid(hid: $hid));
                if (empty($orgCode)){
                    Cacheable::USER_CENTER_ORG_CODE->delete((string)$orgId); // 清空缓存
                    throw new ServiceException("机构编码未找到");
                }
                return $orgCode;
            }
        );
        return static::init((string) $orgCode);
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setOrgCode(string $value): void
    {
        if ($value === $this->orgCode) {
            return;
        }

        $this->orgCode = $value;
    }

    /**
     * @return string
     */
    public function getOrgCode(): string
    {
        return $this->orgCode;
    }

    /**
     * @param string $orgCode
     * @param callable $callback
     *
     * @return mixed
     */
    public static function with(string $orgCode, callable $callback)
    {
        $context = static::instance();

        $latestOrgCode = $context->getOrgCode();

        $context->setOrgCode($orgCode);

        try {
            return $callback();
        } finally {
            $context->setOrgCode($latestOrgCode);
        }
    }
}
