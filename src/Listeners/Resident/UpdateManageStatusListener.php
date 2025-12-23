<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Listeners\Resident;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Events\Callback\ManageStatusChanged;
use BrightLiu\LowCode\Services\Resident\ResidentArchiveService;

class UpdateManageStatusListener
{
    public function __invoke(ManageStatusChanged $event)
    {
        if (empty($resident = $event->getResidentInfo())) {
            Logger::EVENT->error('[UpdateManageStatusListener]无法获取到居民信息', $event->toArray());

            return null;
        }

        $event->initContext();

        $attributes = $this->fetchAttributes($event);

        if (empty($attributes = array_filter($attributes))) {
            return;
        }

        ResidentArchiveService::make()->updateInfo($event->userId, $attributes);
    }

    protected function fetchAttributes($event): array
    {
        $attributes = [];

        // 仅管理时，更新纳管状态
        if ($this->isManage($event)) {
            $attributes['manage_org_code'] = $event->arcCode;
            $attributes['manage_doctor_name'] = $event->operatorName;
            $attributes['manage_doctor_code'] = $event->operatorId;
            $attributes['manage_start_at'] = now()->format('Y-m-d H:i:s');
        }

        $attributes['manage_status'] = $event->manageStatus;
        $attributes['biz_mng_flg'] = $event->manageStatus;

        return $attributes;
    }

    /**
     * 判断是否为纳管
     */
    protected function isManage($event): bool
    {
        return 2 == $event->manageStatus;
    }
}
