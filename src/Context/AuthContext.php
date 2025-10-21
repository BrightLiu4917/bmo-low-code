<?php
namespace BrightLiu\LowCode\Context;
use Illuminate\Support\Collection;

/**
 * Auth上下文
 */
final class AuthContext
{
    /**
     * @var string
     */
    protected string $systemCode = '';

    /**
     * @var int
     */
    protected int $orgId = 0;

    /**
     * @var string
     */
    protected string $token = '';

    /**
     * @var string
     */
    protected string $requestSource = '';

    /**
     * @var null|Collection
     */
    protected ?Collection $allowDiseases = null;

    /**
     * @var null|array
     */
    protected ?array $dataPms = null;

    /**
     * @var null|array
     */
    protected ?array $roles = null;

    /**
     * @return static
     */
    public static function instance(): static
    {
        return app('context:auth');
    }

    /**
     * @param string $systemCode
     * @param int $orgId
     * @param string $token
     * @param string $requestSource
     *
     * @return static
     */
    public static function init(string $systemCode, int $orgId, string $token, string $requestSource): static
    {
        return tap(
            static::instance(),
            function (AuthContext $context) use ($systemCode, $orgId, $token, $requestSource) {
                $context->setSystemCode($systemCode);
                $context->setOrgId($orgId);
                $context->setToken($token);
                $context->setRequestSource($requestSource);
            }
        );
    }

    /**
     * @return string
     */
    public function getSystemCode(): string
    {
        return $this->systemCode;
    }

    /**
     * @return int
     */
    public function getOrgId(): int
    {
        return $this->orgId;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getRequestSource(): string
    {
        return $this->requestSource;
    }

    /**
     * @return Collection
     */
    public function getAllowDiseases(): Collection
    {
        if (is_null($this->allowDiseases)) {
            $this->allowDiseases = $this->fetchAllowDiseases();
        }

        return collect($this->allowDiseases);
    }



    /**
     * @param string $value
     *
     * @return void
     */
    public function setSystemCode(string $value): void
    {
        $this->systemCode = $value;
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setOrgId(int $value): void
    {
        $this->orgId = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setToken(string $value): void
    {
        $this->token = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setRequestSource(string $value): void
    {
        $this->requestSource = $value;
    }

    /**
     * @param Collection $value
     *
     * @return void
     */
    public function setAllowDisease(Collection $value): void
    {
        $this->allowDiseases = $value;
    }

    /**
     * @param array $value
     *
     * @return void
     */
    public function setDataPms(array $value): void
    {
        $this->dataPms = $value;
    }

    /**
     * @param array $value
     *
     * @return void
     */
    public function setRoles(array $value): void
    {
        $this->roles = $value;
    }
}
