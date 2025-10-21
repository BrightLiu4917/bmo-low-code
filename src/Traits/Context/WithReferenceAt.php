<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits\Context;

use Illuminate\Support\Carbon;

trait WithReferenceAt
{
    /**
     * @var null|Carbon
     */
    protected ?Carbon $referenceAt = null;

    /**
     * @var null|Carbon
     */
    protected ?Carbon $currentAt = null;

    /**
     * @param null|string|Carbon $referenceAt
     *
     * @return void
     */
    public function initReferenceAt(null|string|Carbon $referenceAt = null): void
    {
        if (empty($this->currentAt)) {
            $this->currentAt = now();
        }

        if (!empty($referenceAt)) {
            $this->referenceAt = Carbon::parse($referenceAt);
        }
    }

    /**
     * @param null|callable $callable
     *
     * @return void
     */
    public function transformReferenceAt(?callable $callable = null): void
    {
        $this->initReferenceAt(
            transform($this->getReferenceAt(), $callable)
        );
    }

    /**
     * @param mixed $referenceAt
     *
     * @return static
     */
    public function byReferenceAt(mixed $referenceAt = null): static
    {
        $this->referenceAt = match (true) {
            $referenceAt instanceof Carbon => $referenceAt->clone(),
            is_string($referenceAt) => Carbon::parse($referenceAt),
            is_object($referenceAt) && method_exists($referenceAt, 'getRawReferenceAt') => $referenceAt->getRawReferenceAt(),
            default => null
        };

        return $this;
    }

    /**
     * @return Carbon
     */
    public function getReferenceAt(): Carbon
    {
        if (empty($this->referenceAt) && empty($this->currentAt)) {
            $this->initReferenceAt();
        }

        return !empty($this->referenceAt) ? $this->referenceAt->clone() : $this->currentAt->clone();
    }

    /**
     * @return null|Carbon
     */
    public function getRawReferenceAt(): ?Carbon
    {
        return !empty($this->referenceAt) ? $this->referenceAt->clone() : null;
    }
}
