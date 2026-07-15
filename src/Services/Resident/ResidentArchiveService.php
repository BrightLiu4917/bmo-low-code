<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Services\CrowdKitService;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Carbon\Carbon;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 居民档案相关
 */
class ResidentArchiveService extends BaseService
{
    use WithContext;

    /**
     * 获取基础信息
     *
     * @return array{info:array,following:?FollowResident,crowd_info:Collection}
     *
     * @throws ServiceException
     */
    public function getBasicInfo(string $empi): array
    {
        // 基本信息
        $info = ResidentService::make()->getInfo($empi);

        if (empty($info)) {
            throw new ServiceException('居民不存在');
        }

        $kitSrv = CrowdKitService::instance();

        // 关注状态
        $following = FollowResidentService::make()->getFollowing($empi);

        // 人群分类
        $crowdInfo = $kitSrv->rescue->getCrowdTypes($empi, true);

        // 出组信息
        $outGroupInfo = [
            'status' => $this->resolveOutGroupStatus($empi)
        ];

        return [
            'info' => $info,
            'following' => $following,
            'crowd_info' => $crowdInfo,
            'out_group_info' => $outGroupInfo,
        ];
    }

    protected function resolveOutGroupStatus(string $empi): int
    {
        $baselineConfig = config('low-code.bmo-baseline.database.default');

        if (!empty($baselineConfig)) {
            $connectionName = 'lowcode:bmo-baseline';

            if (!config()->has("database.connections.{$connectionName}")) {
                config()->set("database.connections.{$connectionName}", $baselineConfig);
            }

            $query = DB::connection($connectionName)->table('org_patient_out');
        } else {
            $query = DB::table('org_patient_out');
        }

        $records = $query
            ->where('patient_id', $empi)
            ->where('disease_code', $this->getDiseaseCode())
            ->where('scene_code', $this->getSceneCode())
            ->where('is_deleted', 0)
            ->get(['out_reason', 'org_code']);

        $manageOrgCodes = $this->getDataPermissionManageOrgArr(true);

        foreach ($records as $record) {
            // 存在 out_reason = 101 的数据则表示已出组
            if ((int) $record->out_reason === 101) {
                return 1;
            }

            // 存在 out_reason != 101 且机构为当前数据权限内机构的出组记录，则表示已出组
            if ((int) $record->out_reason !== 101 && in_array($record->org_code, $manageOrgCodes, true)) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * 获取信息
     *
     * @throws ServiceException
     */
    public function getInfo(string $empi): array
    {
        $info = ResidentService::make()->getInfo($empi);

        if (empty($info)) {
            throw new ServiceException('居民不存在');
        }

        return (array) $info;
    }

    /**
     * 更新信息
     *
     * @throws ServiceException
     */
    public function updateInfo(string $empi, array $attributes, Carbon|string $updatedAt = '', ?int $dataSource = null): void
    {
        // TODO: 待完善
        $guarded = ['id_crd_no', 'empi', 'is_deleted', 'gmt_created', 'gmt_modified'];

        $attributes = array_diff_key($attributes, array_flip($guarded));

        ResidentService::make()->updateInfo($empi, $attributes, $updatedAt, $dataSource);
    }
}
