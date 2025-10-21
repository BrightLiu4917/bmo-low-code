<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\Disease;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\LowCode\Disease;
use Gupo\BetterLaravel\Http\BaseController;
use BrightLiu\LowCode\Requests\Disease\DiseaseRequest;
use BrightLiu\LowCode\Resources\Disease\ShowResource;
use BrightLiu\LowCode\Resources\Disease\ListResource;
use BrightLiu\LowCode\Services\LowCode\LowCodeDiseaseService;

/**
 * @Class
 * @Description: 疾病管理
 * @created    : 2025-10-01 10:54:45
 * @modifier   : 2025-10-01 10:54:45
 */
final class DiseaseController extends BaseController
{
    /**
     * @param LowCodeDiseaseService $diseaseService
     */
    public function __construct(private readonly LowCodeDiseaseService $diseaseService,
    ) {

    }

    /**
     * @param \BrightLiu\LowCode\Requests\Disease\DiseaseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(DiseaseRequest $request): JsonResponse
    {
        $args = $request->except(['id']);
        if ($this->diseaseService->create($args)) {
            return $this->responseSuccess();
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
        $name = (string)$request->input('name','');
        $list = Disease::query()
            ->when($name !== '',function($query)use($name){
            $query->where('name','like',"%{$name}%");
        })
//            ->with(['creator','updater'])
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->customPaginate(true);
        return $this->responseData($list,ListResource::class);

    }

    /**
     * @param \BrightLiu\LowCode\Requests\Disease\DiseaseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DiseaseRequest $request): JsonResponse
    {
        $id = (int)$request->input('id',0);
        return $this->responseData($this->diseaseService->show($id),ShowResource::class);
    }

    /**
     * @param \BrightLiu\LowCode\Requests\Disease\DiseaseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(DiseaseRequest $request): JsonResponse
    {
        $id = (int)$request->input('id',0);
        if ($result = $this->diseaseService->update($request->post(),$id)){
            return $this->responseSuccess('',$result);
        }
        return $this->responseError();
    }

    /**
     * @param \BrightLiu\LowCode\Requests\Disease\DiseaseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DiseaseRequest $request): JsonResponse
    {
        $id = (int)$request->input('id',0);
        if ($this->diseaseService->delete($id)){
            return $this->responseSuccess();
        }
        return $this->responseError();
    }
}
