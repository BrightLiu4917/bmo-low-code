<?php


namespace BrightLiu\LowCode\Context;



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
            }
        );
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
