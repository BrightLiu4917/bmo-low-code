<?php

namespace BrightLiu\LowCode\Core\Traits;

trait InstancePropertiesTrait
{
    public function getInstanceProperties(): array
    {
        return get_object_vars($this);
    }

    public function setInstanceProperties(array $options): void
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
