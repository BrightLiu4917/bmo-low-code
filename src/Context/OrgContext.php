<?php


namespace BrightLiu\LowCode\Context;




use BrightLiu\LowCode\Enums\Foundation\Logger;
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

    protected array $manageAreaCodes = [];

    protected array $manageOrgCodes = [];


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
    public static function init(string $orgCode = '',string $arcCode = '',array $manageAreaCodes = [],array $manageOrgCodes = []): static
    {
        return tap(
            static::instance(),
            function (OrgContext $context) use ($orgCode,$arcCode,$manageAreaCodes,$manageOrgCodes) {
                $context->setOrgCode($orgCode);
                $context->setArcCode($arcCode);

                $context->setManageOrgCodes($manageOrgCodes);

                $context->setManageAreaCodes($manageAreaCodes);
                //这里获取用户中心的arc 信息
                try {
                    $data = BmoAuthApiService::instance()->getArcDetail($arcCode);
                    if (!empty($data)){
                        $context->setArcName($data['name'] ?? '');
                        $context->setArcType($data['arc_type'] ?? '');
                    }
                }catch (\Throwable $throwable){
                    Logger::API_SERVICE->error(
                        '获取用户中心机构地区编码错误',
                        [
                            'error'    => $throwable->getMessage(),
                            'org_code' => $orgCode,
                            'arc_code' => $arcCode,
                            'message'  => $throwable->getMessage(),
                            'file'     => $throwable->getFile(),
                            'trace'    => $throwable->getTraceAsString(),
                            'line'     => $throwable->getLine(),
                        ]);
                }
            }
        );
    }

    public function setManageAreaCodes(array $value): void
    {
        if ($value === $this->manageAreaCodes) {
            return;
        }

        $this->manageAreaCodes = $value;
    }

    public function setManageOrgCodes(array $value): void
    {
        if ($value === $this->manageOrgCodes) {
            return;
        }

        $this->manageOrgCodes = $value;
    }

    public function getManageOrgCode(): array
    {
        return $this->manageOrgCodes;
    }



    public function getManageAreaCode(): array
    {
        return $this->manageAreaCodes;
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
