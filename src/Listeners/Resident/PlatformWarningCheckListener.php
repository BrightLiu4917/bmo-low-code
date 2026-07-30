<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Listeners\Resident;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Events\Resident\ResidentInfoUpdated;
use BrightLiu\LowCode\Services\BmpCheetahMedicalPlatformApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlatformWarningCheckListener implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __invoke(ResidentInfoUpdated $event): void
    {
        $vitals = [];
        foreach ($event->getAttributes() as $name => $value) {
            $vitals[] = [
                'name'        => $name,
                'value'       => (string) $value,
                'create_time' => $event->updatedAt ?: now()->toDateTimeString(),
            ];
        }

        if (empty($vitals)) {
            return;
        }

        try {
            BmpCheetahMedicalPlatformApiService::make()
                ->byDisease($event->context['disease_code'] ?? '')
                ->byScene($event->context['scene_code'] ?? '')
                ->byOrgCode($event->context['org_code'] ?? '')
                ->warningCheck($event->getEmpi(), $vitals);
        } catch (\Throwable $e) {
            Logger::BMP_CHEETAH_MEDICAL_DEBUG->error(
                '[PlatformWarningCheckListener] 调用预警检查接口失败',
                ['empi' => $event->getEmpi(), 'error' => $e->getMessage()]
            );
        }
    }
}
