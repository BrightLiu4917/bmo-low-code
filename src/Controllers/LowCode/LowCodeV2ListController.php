<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use Illuminate\Support\Arr;
use BrightLiu\LowCode\Tools\Uuid;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Enums\Model\AdminPreference\SceneEnum;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Services\BmpBaseLineService;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource;
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
use BrightLiu\LowCode\Services\LowCode\LowCodeCombiService;
use BrightLiu\LowCode\Services\LowCode\AdminPreferenceService;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;

/**
 * 低代码-列表
 */
final class LowCodeV2ListController extends BaseController
{
    use WithOrgContext;

    /**
     * @param  \BrightLiu\LowCode\Services\LowCode\LowCodeListService  $service
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
//            ->byContextDisease()
            ->where(
                'list_type',
                '<>',
                ListTypeEnum::GENERAL
            )->select([
                'id',
                'admin_name',
                'code',
                'parent_code',
                'crowd_type_code',
                'route_group',
            ])->customPaginate(true);

        try {
            // 获取个性化菜单
            $personalizeModules = LowCodePersonalizeModule::query()->byContextDisease()->where(
                    'org_code',
                    $this->getAffiliatedOrgCode()
                )->where(
                    'module_type',
                    'crowd_patients'
                )->orderByDesc('weight')->get([
                    'id',
                    'title',
                    'module_id',
                    'module_type',
                    'metadata',
                    'created_at',
                ])->map(fn(
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


            $combiSrv = LowCodeCombiService::make();

            // 个性化菜单的code由 列表code + 特征人群code  组成
            $personalizeList = collect();
            $personalizeModules->each(
                function ($personalizeModule, $i) use (
                    $personalizeList,
                    $list,
                    $combiSrv
                ) {
                    // TODO: 目录所有个性化菜单共用tabs，待完善
                    $list->each(
                        function ($item) use (
                            $i,
                            $personalizeModule,
                            $personalizeList,
                            $combiSrv
                        ) {
                            $listItem = clone $item;

                            // 虚拟ID，避免主键冲突
                            $listItem['id']   = ($i + 1) * 10000000000 +
                                $listItem['id'];
                            $listItem['route_group']
                                              = $personalizeModule['route_group'];
                            $listItem['code'] = $combiSrv->combiListCode(
                                (string)$listItem['code'],
                                (string)$personalizeModule->code
                            );

                            $personalizeList->push($listItem);
                        }
                    );
                }
            );

            $list->setCollection($personalizeList);
        } catch (\Throwable $e) {
        }

        return $this->responseData($list, SimpleListSource::class);
    }

    /**
     * 查询数量
     */
    public function queryCount(Request $request): JsonResponse
    {
        $data  = [];
        $codes = $request->input('codes', null);

        $listSrv = LowCodeListService::make();

        // TODO: 按人群患者查询时，需要携带条件
        foreach ($codes as $key => $code) {
            $data[$key]['crowd_type_total_count'] = $listSrv->queryCount(
                [['code' => $code]]
            );
            $data[$key]['crowd_type_code']        = $code;
        }
        return $this->responseData($data);
    }

    /**
     * 预请求
     */
    public function pre(Request $request, LowCodeListService $srv): JsonResponse
    {
        $code = (string)$request->input('code', null);

        $data = $srv->pre(
            LowCodeCombiService::instance()->resolveListCode($code)
        );

        try {
            $data['pre_config']['column'] = AdminPreferenceService::make()
                ->handleColumnConfig(
                    listCode: $code,
                    columnConfig: $data['pre_config']['column'] ?? []
                );
        } catch (\Throwable $e) {
        }

        return $this->responseData($data);
    }

    /**
     * 查询数据
     */
    public function query(Request $request)
    {
        $inputArgs = $request->input('input_args');
        $export    = $request->input('export', false);
        $isSimplePaginate = (bool) $request->input('is_simple_paginate', false);

        $data      = LowCodeListService::instance()->query(
            inputArgs: $inputArgs,
            export: $export,
            isSimplePaginate: $isSimplePaginate
        );
        try {
            // 获取人群分类信息：在新版中已通过更优的方式获取，这里做兜底兼容处理
            /** @see \BrightLiu\LowCode\Services\CustomQueryEngineService::attachCrowdGroup */
            if (!empty($data) && $data->isNotEmpty() && !isset($data->first()->_crowds)) {
                // 追加人群分类信息
                $empis = $data->pluck('empi')->toArray();

                //查询人群分类表里人群
                $crowds  = BmpBaseLineService::instance()->getPatientCrowds(empis:$empis,selectType: 0);
                Logger::LOW_CODE_LIST->debug('获取人群分类',[$crowds]);
                $grouped = [];
                foreach ($crowds as $item) {
                    $empi = $item->empi;
                    if (!isset($grouped[$empi])) {
                        $grouped[$empi] = [];
                    }
                    $grouped[$empi][] = [
                        'group_id'   => $item->group_id,
                        'group_name' => $item->group_name,
                    ];
                }

                //$grouped 将患者的人群分类收集到一起
                $data->each(function ($item) use ($grouped) {
                    if (isset($grouped[$item->empi])) {
                        $res           = (array)$grouped[$item->empi ?? ''];
                        $item->_crowds = implode(
                            ',',
                            array_filter(array_column($res ?? [], 'group_name'))
                        );
                    }
                });
            }

            if ($export) {
                $code = data_get($inputArgs, '0.code', '');
                if (!empty($code)) {
                    $code = explode('#', $code);
                }
                $headers        = $this->service->pre(
                    (Arr::first($code, null, ''))
                );
                $headersColumns = data_get($headers, 'pre_config.column', []);
                $code           = addslashes(Arr::first($code, null, '')); // 防止SQL注入
                $adminName      = LowCodeList::query()->where('code', $code)
                    ->value('admin_name');
                $filename       = ($adminName ?? 'export').'-'.
                    date('Y-m-d-H-i-s');
                return $this->service->exportWithMaatExcel(
                    data: $data,
                    filename: $filename,
                    headers: $headersColumns
                );
            }
        } catch (\Throwable $exception) {
            Logger::LOW_CODE_LIST->error('list-query-error', [
                'inputs'      => $inputArgs ?? [],
                'error'       => $exception->getMessage(),
                'trace'       => $exception->getTraceAsString(),
                'line'        => $exception->getLine(),
                'file'        => $exception->getFile(),
                'export_data' => $export ?? false,
            ]);
        }
        return $this->responseData($data, class_map(QuerySource::class));
    }

    /**
     * 可选列
     */
    public function optionalColumns(
        Request $request,
        CrowdKitService $srv,
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

        $columns = AdminPreference::query()->where(
                'scene',
                SceneEnum::LIST_COLUMNS
            )->where('pkey', $listCode)->value('pvalue');

        // 缺省时，从low_code_part中解析获取
        if (empty($columns)) {
            $listCode    = LowCodeCombiService::instance()->resolveListCode(
                $listCode
            );
            $lowCodeList = LowCodeList::query()->where('code', $listCode)
                ->first(['template_code_column']);
            if (empty($lowCodeList)) {
                return $this->responseData(['items' => []]);
            }

            $partCodes = LowCodeTemplateHasPart::query()->where(
                    'template_code',
                    $lowCodeList['template_code_column']
                )->pluck('part_code');

            if ($partCodes->isEmpty()) {
                return $this->responseData(['items' => []]);
            }

            $columns = LowCodePart::query()->whereIn(
                    'code',
                    $partCodes->toArray()
                )->where('content_type', 1)->pluck('content')->pluck('key');
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

        $preference = AdminPreference::query()->where(
                'scene',
                SceneEnum::LIST_COLUMNS
            )->where('pkey', $listCode)->first();

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
}
