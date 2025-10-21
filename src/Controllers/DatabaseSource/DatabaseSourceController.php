<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Controllers\DatabaseSource;

use Illuminate\Http\JsonResponse;
use App\Models\LowCode\DatabaseSource;
use Gupo\BetterLaravel\Http\BaseController;
use BrightLiu\LowCode\Resources\DatabaseSource\ShowResource;
use BrightLiu\LowCode\Resources\DatabaseSource\ListResource;
use BrightLiu\LowCode\Services\LowCode\LowCodeDatabaseSourceService;
use BrightLiu\LowCode\Requests\Foundation\DatabaseSourceRequest\DatabaseSourceRequest;

/**
 * @Class:DatabaseSourceController
 * @Description:数据库源管理
 * @created    : 2025-10-01 10:52:20
 * @modifier   : 2025-10-01 10:52:20
 */
final class DatabaseSourceController extends BaseController
{
    /**
     * @param LowCodeDatabaseSourceService $service
     */
    public function __construct(private readonly LowCodeDatabaseSourceService $service)
    {

    }

    /**
     * @param DatabaseSourceRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(DatabaseSourceRequest $request): JsonResponse
    {
        $this->service->create($request->input());
        return $this->responseSuccess();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(): JsonResponse
    {
        $list = DatabaseSource::query()
            //todo 权限还没确认
//            ->byContextDisease()

//            ->with(
//                ['creator:id,realname', 'updater:id,realname',
//                 'disease:code,name']
//            )
            ->select(
                ['id', 'disease_code', 'code', 'name', 'host', 'code',
                 'database', 'table', 'port', 'options', 'creator_id',
                 'created_at', 'updated_at', 'source_type', 'updater_id']
            )->customPaginate(true);
        return $this->responseData($list, ListResource::class);
    }

    /**
     * @param DatabaseSourceRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DatabaseSourceRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if (empty($result = $this->service->show($id))) {
            return $this->responseError('数据不存在');
        }
        return $this->responseData(
            $result, ShowResource::class
        );
    }

    /**
     * @param DatabaseSourceRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DatabaseSourceRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        if ($this->service->delete($id)) {
            return $this->responseSuccess('');
        }
        return $this->responseError();
    }

    /**
     * @param DatabaseSourceRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(DatabaseSourceRequest $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
        $args = $request->input();
        $this->service->update($id, $args);
        return $this->responseSuccess();
    }
}
