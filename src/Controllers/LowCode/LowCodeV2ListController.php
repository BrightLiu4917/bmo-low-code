<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use App\Http\Resources\LowCode\ListResource;
use App\Http\Resources\LowCode\BasicInfoResource;
use BrightLiu\LowCode\Enums\Model\AdminPreference\SceneEnum;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Services\BmpBaseLineService;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Resources\LowCode\V2\LowCodeList\SimpleListSource;
use BrightLiu\LowCode\Models\AdminPreference;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use BrightLiu\LowCode\Models\LowCodeList;
use BrightLiu\LowCode\Models\LowCodePart;
use BrightLiu\LowCode\Models\LowCodeTemplateHasPart;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Services\LowCode\LowCodeListService;
use BrightLiu\LowCode\Services\CrowdKitService;

/**
 * 低代码-列表
 */
final class LowCodeV2ListController extends BaseController
{
    /**
     * @param \BrightLiu\LowCode\Services\LowCode\LowCodeListService $service
     */
    public function __construct(protected LowCodeListService $service)
    {

    }

    /**
     * 简单列表
     */
    public function simpleList(Request $request): JsonResponse
    {
        $list = LowCodeList::query()
                           ->where('list_type', '<>', ListTypeEnum::GENERAL)
                           ->select([
                               'id', 'admin_name', 'code', 'parent_code',
                               'crowd_type_code', 'route_group',
                           ])
                           ->customPaginate(true);

        try {
            // 追加个性化菜单
            $personalizeModuels = LowCodePersonalizeModule::query()
                                                          ->where('module_type',
                                                              'crowd_patients')
                                                          ->orderByDesc('weight')
                                                          ->get([
                                                              'id', 'title',
                                                              'module_id',
                                                              'module_type',
                                                              'metadata',
                                                              'created_at',
                                                          ])
                                                          ->map(fn (
                                                              LowCodePersonalizeModule $item,
                                                          ) => new LowCodeList([
                                                              'id'          => $item->id,
                                                              'admin_name'  => $item->title,
                                                              'code'        => $item->module_id,
                                                              'parent_code' => '',
                                                              'route_group' => [
                                                                  $item->metadata['path'],
                                                              ],
                                                          ]));

            // 修改$list分页对象的数据
            $list->setCollection($list->getCollection()
                                      ->merge($personalizeModuels));
        } catch (\Throwable $e) {
        }

        return $this->responseData($list, SimpleListSource::class);
    }

    /**
     * 查询数量
     */
    public function queryCount(Request $request): JsonResponse
    {
        $data = [];
        $codes = $request->input('codes', null);
        // TODO: 按人群患者查询时，需要携带条件
        foreach ($codes as $key => $code) {
            $data[$key]['crowd_type_total_count']
                = QueryEngineService::instance()
                                    ->autoClient()
                                    ->whereListPresetCondition($code)
                                    ->setCache(10)
                                    ->getCountResult();
            $data[$key]['crowd_type_code'] = $code;
        }
        return $this->responseData($data);
    }

    /**
     * 预请求
     */
    public function pre(Request $request, LowCodeListService $srv): JsonResponse
    {
        $code = (string)$request->input('code', null);

        $data = $srv->pre($this->covertCrowdPatientCode($code));

        try {
            $preference = AdminPreference::query()
                                         ->where('scene',
                                             SceneEnum::LIST_COLUMNS)
                                         ->where('pkey', $code)
                                         ->value('pvalue');

            if (!empty($preference)) {
                $data['pre_config']['column'] = array_map(
                    fn ($item) => [
                        'title' => $item['name'],
                        'key'   => $item['column'],
                    ],
                    $preference
                );
            }
        } catch (\Throwable) {
        }

        return $this->responseData($data);
    }

    /**
     * 查询数据
     */
    public function query(Request $request): JsonResponse
    {
        $inputArgs = $request->input('input_args');
        $codes     = $this->covertCrowdPatientCode(array_column($inputArgs,
            'code'));
        $inputArgs = array_map(
            function($item) use ($codes) {
                if ($item['code'] !== $codes[$item['code']]) {
                    $item['filters'][] = ['crowd_id', '=', $item['code']];
                }

                // 将人群code映射到"通用人群页"
                $item['code'] = $codes[$item['code']] ?? $item['code'];
                return $item;
            },
            $inputArgs
        );
        $data      = $this->service->query($inputArgs);
        try {
            // 追加人群分类信息
            $userIds = $data->pluck('user_id')->toArray();

            //查询人群分类表里人群
            $crowds = BmpBaseLineService::instance()->getPatientCrowds($userIds);
            $grouped = [];
            foreach ($crowds as $item) {
                $userId = $item->user_id;
                if (!isset($grouped[$userId])) {
                    $grouped[$userId] = [];
                }
                $grouped[$userId][] = [
                    'group_id'   => $item->group_id,
                    'group_name' => $item->group_name,
                ];
            }

            //$grouped 将患者的人群分类收集到一起

            $data = $data->each(function($item) use ($grouped) {
                if (isset($grouped[$item->user_id])){
                    $res = (array)$grouped[($item->user_id ?? '')];
                    return $item->_crowds = implode(',',
                        array_column($res ?? [], 'group_name'));
                }
            });
        } catch (\Throwable $exception) {
        }
        return $this->responseData($data, ListResource::class);
    }

    /**
     * 可选列
     */
    public function optionalColumns(Request $request, CrowdKitService $srv,
    ): JsonResponse {
        $data = $srv->getOptionalColumns();

        // TODO：写法待完善
        // 预设人群分类模块
        $data->push(
            [
                'id'      => 'preset',
                'name'    => '人群信息',
                'columns' => [
                    [
                        'id'     => 'preset_crowds',
                        'name'   => '人群分类',
                        'type'   => 'array',
                        'column' => '_crowds',
                    ],
                ],
            ],
        );

        return $this->responseData(['items' => $data]);
    }

    /**
     * 获取列偏好设置
     */
    public function getColumnPreference(Request $request): JsonResponse
    {
        $listCode = (string)$request->input('list_code', '');

        if (empty($listCode)) {
            return $this->responseError('参数错误');
        }

        $columns = AdminPreference::query()
                                  ->where('scene', SceneEnum::LIST_COLUMNS)
                                  ->where('pkey', $listCode)
                                  ->value('pvalue');

        // 缺省时，从low_code_part中解析获取
        if (empty($columns)) {
            $listCode = $this->covertCrowdPatientCode($listCode);

            $lowCodeList = LowCodeList::query()->where('code', $listCode)
                                      ->first(['template_code_column']);
            if (empty($lowCodeList)) {
                return $this->responseData(['items' => []]);
            }

            $partCodes = LowCodeTemplateHasPart::query()
                                               ->where('template_code',
                                                   $lowCodeList['template_code_column'])
                                               ->pluck('part_code');

            if ($partCodes->isEmpty()) {
                return $this->responseData(['items' => []]);
            }

            $columns = LowCodePart::query()
                                  ->whereIn('code', $partCodes->toArray())
                                  ->where('content_type', 1)
                                  ->pluck('content')
                                  ->pluck('key');
        } else {
            $columns = array_column($columns, 'column');
        }

        return $this->responseData(['items' => $columns]);
    }

    /**
     * 更新列偏好设置
     */
    public function updateColumnPreference(Request $request): JsonResponse
    {
        $listCode = (string)$request->input('list_code', '');

        $columns = (array)$request->input('columns', []);

        if (empty($listCode)) {
            return $this->responseError('参数错误');
        }

        $preference = AdminPreference::query()
                                     ->where('scene', SceneEnum::LIST_COLUMNS)
                                     ->where('pkey', $listCode)
                                     ->first();

        if (empty($preference)) {
            AdminPreference::query()->create([
                'scene'  => SceneEnum::LIST_COLUMNS,
                'pkey'   => $listCode,
                'pvalue' => $columns,
                //                'admin_id' => AdminContext::instance()->getAdminId(),
            ]);
        } else {
            $preference->update(['pvalue' => $columns]);
        }

        return $this->responseSuccess();
    }

    public function basicInfo(Request $request)
    {
        $userId = $request->input('user_id', '');
        $data   = LowCodeResidentService::instance()->basicInfo($userId);
        return $this->responseData($data, BasicInfoResource::class);
    }

    /**
     * 转换人群患者编码
     */
    protected function covertCrowdPatientCode(string|array $codes): string|array
    {
        return Cache::remember('crowd_patient_code:'.md5(json_encode($codes)),
            60 * 5, function() use ($codes) {
                $isOnce = !is_array($codes);

                $codes = (array)$codes;

                $existsCodes = LowCodeList::query()->whereIn('code', $codes)
                                          ->pluck('code')->toArray();

                // TODO: 写法待完善
                $crowdPatientCode = LowCodeList::query()->byContextDisease()
                                               ->where('admin_name',
                                                   '人群患者列表')
                                               ->value('code');

                return transform(
                    array_combine($codes,
                        array_map(fn ($code) => in_array($code, $existsCodes) ?
                            $code : $crowdPatientCode, $codes)),
                    fn ($value) => $isOnce ? end($value) ?? '' : $value
                );
            });
    }
}
