<?php


namespace BrightLiu\LowCode\Context;



use BrightLiu\LowCode\Services\BmoAuthApiService;

/**
 * 机构上下文
 */
final class OrgContext
{
    /**
     * @var string
     */
    protected string $orgCode = '';

    protected string $arcCode = '';

    protected string $arcName = '';
    protected string $arcType = '';


    /**
     * @return static
     */
    public static function instance(): static
    {
        return app('context:org');
    }

    /**
     * @param string $orgCode
     * @param  string  $arcCode
     *
     * @return static
     */
    public static function init(string $orgCode,string $arcCode): static
    {
        return tap(
            static::instance(),
            function (OrgContext $context) use ($orgCode,$arcCode) {
                $context->setOrgCode($orgCode);
                $context->setArcCode($arcCode);

                //这里获取用户中心的arc 信息
                try {
                    $data = BmoAuthApiService::instance()->getArcDetail($arcCode);
                    if (!empty($data)){
                        $context->setArcName($data['name'] ?? '');
                        $context->setArcType($data['arc_type'] ?? '');
                    }
                }catch (\Throwable $throwable){

                }
            }
        );
    }

    public function setArcName(string $value): void
    {
        if ($value === $this->arcName) {
            return;
        }

        $this->arcName = $value;
    }

    public function setArcType(string $value): void
    {
        if ($value === $this->arcType) {
            return;
        }

        $this->arcType = $value;
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

    public function setArcCode(string $value): void
    {
        if ($value === $this->arcCode) {
            return;
        }

        $this->arcCode = $value;
    }

    /**
     * @return string
     */
    public function getOrgCode(): string
    {
        return $this->orgCode;
    }

    public function getArcName(): string
    {
        return $this->arcName;
    }

    public function getArcType(): string
    {
        return $this->arcType;
    }

    public function getArcCode(): string
    {
        return $this->arcCode;
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
