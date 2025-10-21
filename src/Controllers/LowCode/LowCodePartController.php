<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\LowCode\LowCodePart;
use Gupo\BetterLaravel\Http\BaseController;
use App\Services\LowCode\LowCodeQueryEngineService;
use BrightLiu\LowCode\Services\LowCode\LowCodePartService;
use BrightLiu\LowCode\Resources\LowCode\LowCodePart\ListSource;
use BrightLiu\LowCode\Requests\LowCode\LowCodePartRequest;
use BrightLiu\LowCode\Resources\LowCode\LowCodePart\ShowSource;

/**
 * 低代码-零件
 */
final class LowCodePartController extends BaseController
{

    /**
     * @param \BrightLiu\LowCode\Services\LowCode\LowCodePartService $service
     */
    public function __construct(private readonly LowCodePartService $service)
    {

    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodePartRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(LowCodePartRequest $request): JsonResponse
    {
        $args = $request->except(['id','code']);
        if ($this->service->create($args)) {
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
        $partType = (int)$request->input('part_type', 0);
        $contentType = (int)$request->input('content_type', 0);
        $name = (string)$request->input('name', '');
        $data = LowCodePart::query()->when(
            $partType !== 0, function($query)use($partType) {
            $query->where('part_type', $partType);
        }
        )->when(
            $contentType !== 0, function($query)use($contentType) {
            $query->where('content_type', $contentType);
        }
        )->when(
            $name !== '', function($query)use($name) {
            $query->where('name', $name);
        }
        )->orderByDesc('created_at')
//         ->with(
//            ['updater:id,realname', 'creator:id,realname']
//        )
         ->customPaginate(true);

        return $this->responseData($data, ListSource::class);

    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodePartRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LowCodePartRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($this->service->update($request->post(), $id)) {
            return $this->responseSuccess();
        }
        return $this->responseError();
    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodePartRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LowCodePartRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($result = $this->service->show($id)) {
            return $this->responseData($result, ShowSource::class);
        }
        return $this->responseError();
    }

    /**
     * @param \BrightLiu\LowCode\Requests\LowCode\LowCodePartRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(LowCodePartRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($this->service->delete($id)) {
            return $this->responseSuccess();
        }
        return $this->responseError();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTableFields(): JsonResponse
    {
        //todo 这里是写死的编码 应该根据 疾病编码 动态获取编码
        $query = LowCodeQueryEngineService::instance()
           ->autoClient();
        $fields = $query->setCache(60*60)->getRawResult(
            rawSql:
            'SELECT COLUMN_NAME AS "field_name",
                COLUMN_COMMENT AS "field_comment"
            FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                        bindings:
                            [
                                $query->database,
                                $query->table
                            ]
        );
        return $this->responseSuccess('', $fields);
    }
}
