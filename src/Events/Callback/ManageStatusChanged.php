<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Events\Callback;

use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Services\Resident\ResidentService;

class ManageStatusChanged
{
    public function __construct(
        public readonly int $orgId,
        public readonly string $diseaseCode,
        public readonly string $sceneCode,
        public readonly string $userId,
        public readonly int $manageStatus,
        public readonly string $operatorName,
        public readonly string $operatorId,
        public readonly string $arcCode,
    ) {
    }

    /**
     * 初始化上下文
     */
    public function initContext(): void
    {
        OrgContext::init((string) $this->orgId, $this->arcCode);

        DiseaseContext::init($this->diseaseCode, $this->sceneCode);
    }

    public function toArray(): array
    {
        return [
            'org_id' => $this->orgId,
            'disease_code' => $this->diseaseCode,
            'scene_code' => $this->sceneCode,
            'user_id' => $this->userId,
            'manage_status' => $this->manageStatus,
            'operator_name' => $this->operatorName,
            'operator_id' => $this->operatorId,
            'arc_code' => $this->arcCode,
        ];
    }

    /**
     * 获取居民信息
     */
    public function getResidentInfo(array $columns = ['t2.*', 't1.*']): ?array
    {
        if (empty($this->userId)) {
            return null;
        }

        return ResidentService::instance()->getInfo($this->userId, $columns);
    }
}
