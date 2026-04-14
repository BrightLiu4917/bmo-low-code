<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;

class ExcludeExitedMysqlQueryBuilder extends MysqlQueryBuilder implements ILowCodeQueryBuilder
{
    use WithDiseaseContext, WithOrgContext;

    /**
     * 构建基本的关联查询
     */
    public function relationQueryEngine(array $filters): array
    {
        $filters = parent::relationQueryEngine($filters);

        $recommendEmpi = $this->recommendJoinEmpi('t1.empi');

        // 排除已出组患者
        $this->queryEngine->getQueryBuilder()->whereNotExists(function ($query) use ($recommendEmpi) {
            $query->selectRaw('1')
                ->from('org_patient_out as t10')
                ->whereColumn('t10.patient_id', $recommendEmpi)
                ->whereIn('t10.org_code', $this->getDataPermissionManageOrgArr(true))
                ->where('t10.disease_code', $this->getDiseaseCode())
                ->where('t10.scene_code', $this->getSceneCode())
                ->where('t10.is_deleted', 0);
        });

        return $filters;
    }
}
