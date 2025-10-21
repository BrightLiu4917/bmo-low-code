<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Illuminate\Support\Facades\DB;
use App\Models\LowCode\LowCodePart;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Enums\Model\LowCodePart\PartTypeEnum;

/**
 * 低代码-零件
 */
final class LowCodePartService extends LowCodeBaseService
{
    use CastDefaultFixHelper;
    /**
     * @param array $data
     *
     * @return LowCodePart|null
     */
    public function create(array $data): ?LowCodePart
    {
        $filterArgs = $this->fixInputDataByCasts($data,LowCodePart::class);
        return LowCodePart::query()->create($filterArgs);
    }

    public function show(int $id = 0): LowCodePart|null
    {
        if (!$result = LowCodePart::query()->where('id',$id)->first()){
            throw new ServiceException("ID:{$id}不存在");
        }
        return $result;
    }

    /**
     * @param array $data
     * @param int                     $id
     *
     * @return bool|int
     * @throws ServiceException
     */
    public function update(array $data, int $id = 0)
    {
        if (empty($result = LowCodePart::query()->where('id',$id)->first())) {
            throw new ServiceException("数据{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts($data,LowCodePart::class);
        $this->clearCache($result->code);
        return $result->update($filterArgs);
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id = 0): bool
    {
        if (!$result = LowCodePart::query()->where('id',$id)->first(['id','part_type','code'])) {
            throw new ServiceException("ID:{$id}不存在");
        }

        if ($result->part_type === PartTypeEnum::SYSTEM) {
            throw new ServiceException("系统组件不可删除");
        }

        if (DB::table('low_code_template_has_parts')->where('part_code',$result->code)->exists()){
            throw new ServiceException("组件被模板绑定不可删除");
        }

        $this->clearCache($result->code);
        return $result->delete();
    }

    /**
     * @param string $partCode
     *
     * @return void
     */
    protected function clearCache(string $partCode = '')
    {
       $templateCodes  =  DB::table('low_code_template_has_parts')
           ->where('part_code',$partCode)
           ->pluck('template_code');
       $service = LowCodeTemplateService::instance();
       foreach ($templateCodes as $templateCode) {
           $service->clearCache($templateCode);
       }
    }
}
