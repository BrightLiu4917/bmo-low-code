<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Enums\Foundation\Cacheable;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * 业务平台-资源中心
 */
final class DtCapybaraServiceRcApiService extends LowCodeBaseService
{
    use WithContext, WithAuthContext;

    /**
     * 声明 base_uri
     */
    protected function baseUriVia(): string
    {
        return config('business.bmo-service.dt_capybara_service_rc.uri', '');
    }

    /**
     * 获取区域编码的等级映射
     */
    public function getRegionCodeLevelMapping(string|array $regionCode): array
    {
        return (array) Cacheable::REGION_CODE_LEVEL_MAPPING
            ->make($regionCode)
            ->remember(
                60 * 60 * 6,
                fn () => Arr::get(
                    Http::asJson()
                        ->retry(2)
                        ->timeout(5)
                        ->post(
                            $this->baseUriVia().'innerapi/areaInfo/code-level',
                            ['code_list' => Arr::wrap($regionCode)]
                        )
                        ->json(),
                    'data',
                    []
                )
            );
    }
}
