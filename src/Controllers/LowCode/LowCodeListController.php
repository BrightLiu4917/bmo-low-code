<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use Illuminate\Http\Request;
use App\Models\LowCode\LowCodeList;
use BrightLiu\LowCode\Requests\LowCode\LowCodeListRequest;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use BrightLiu\LowCode\Services\LowCode\LowCodeListService;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\ListSource;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\simpleListSource;

/**
 * @Class
 * @Description:低代码-列表
 * @created    : 2025-10-01 10:55:45
 * @modifier   : 2025-10-01 10:55:45
 */
final class LowCodeListController extends BaseController
{
    /**
     * @param \BrightLiu\LowCode\Services\LowCode\LowCodeListService $service
     */
    public function __construct(protected LowCodeListService $service)
    {

    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodeListRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(LowCodeListRequest $request): JsonResponse
    {
        try {
            $args = $request->except(['id', 'code']);
            if ($this->service->create($args)) {
                return $this->responseSuccess();
            }
        } catch (\Exception $e) {
            return $this->responseError($e->getMessage());
        }
        return $this->responseError();
    }


    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $name = trim($request->input('name', ''));
        $templateCode = trim($request->input('template_code', ''));
        //权限判断
        $query = LowCodeList::query()
//                            ->byContextDisease()->byContextDisease()
            ->orderByRaw("CASE WHEN admin_name = '全部人群' THEN 0 ELSE 1 END asc")
            ->where('list_type', '<>', ListTypeEnum::GENERAL);
        if ($name !== '') {
            $query->where(function($q) use ($name) {
                $q->orWhere('admin_name', 'like', "%{$name}%")
                    ->orWhere('family_doctor_name', 'like', "%{$name}%")
                    ->orWhere('mobile_doctor_name', 'like', "%{$name}%");
            });
        }

        if ($templateCode !== '') {
            $query->where(function($q) use ($templateCode) {
                $q->orWhere('template_code_filter', 'like', "%{$templateCode}%")
                    ->orWhere('template_code_column', 'like',
                        "%{$templateCode}%")
                    ->orWhere('template_code_top_button', 'like',
                        "%{$templateCode}%")
                    ->orWhere('template_code_field', 'like',
                        "%{$templateCode}%")
                    ->orWhere('template_code_button', 'like',
                        "%{$templateCode}%");
            });
        }
        $list = $query
            //->byContextOrg()->byContextDisease()
//                                      ->with([
//                'updater:id,realname', 'creator:id,realname',
//                'crowdType:code,name,color,weight',
//            ])
                                      ->select([
                'id', 'admin_name', 'code', 'parent_code', 'crowd_type_code',
                'route_group', 'admin_weight', 'creator_id', 'updater_id',
            ])->orderByDesc('created_at')->orderByDesc('id')
            ->customPaginate(true);

        return $this->responseData($list, ListSource::class);
    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodeListRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LowCodeListRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($this->service->update($request->post(), $id)) {
            return $this->responseSuccess();
        }
        return $this->responseError();
    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodeListRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LowCodeListRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($result = $this->service->show($id)) {
            return $this->responseSuccess('', $result);
        }
        return $this->responseError();

    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodeListRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(LowCodeListRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($this->service->delete($id)) {
            return $this->responseSuccess();
        }
        return $this->responseError();
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function simpleList(Request $request): JsonResponse
    {
        $list = LowCodeList::query()->byContextOrg()->byContextDisease()
            ->orderByRaw("CASE WHEN admin_name = '全部人群' THEN 0 ELSE 1 END asc")
            ->where('list_type', '<>', ListTypeEnum::GENERAL)->select([
                'id', 'admin_name', 'code', 'parent_code', 'crowd_type_code',
                'route_group',
            ])
//                                                             ->with(['crowdType:name,code,color,weight'])
            ->customPaginate(true);
        return $this->responseData($list, simpleListSource::class);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function pre(Request $request)
    {
        $code = (string)$request->input('code', '');
        return $this->responseSuccess('', $this->service->pre($code));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        $inputArgs = $request->input('input_args');
        return $this->responseData($this->service->query($inputArgs), QuerySource::class);
    }


}
