<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Illuminate\Support\Facades\DB;
use App\Models\LowCode\LowCodePart;
use App\Models\LowCode\LowCodeList;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use App\Models\LowCode\LowCodeTemplate;
use BrightLiu\LowCode\Core\TemplatePartCacheManager;

/**
 * 低代码-模板
 */
final class LowCodeTemplateService extends LowCodeBaseService
{
    use CastDefaultFixHelper;

    /**
     * @param array $data
     *
     * @return LowCodeTemplate|null
     */
    public function create(array $data,
    ): LowCodeTemplate|null {
        $filterArgs = $this->fixInputDataByCasts($data,LowCodeTemplate::class);
        return LowCodeTemplate::query()->create($filterArgs);
    }

    /**
     * @param int $id
     *
     * @return LowCodeTemplate|null
     * @throws ServiceException
     */
    public function show(int $id = 0): LowCodeTemplate|null
    {
        if ($id <= 0) {
            return null; // 防止无效id继续查询和缓存
        }
        if (!$result = LowCodeTemplate::query()->where('id', $id)->with(
                ['bindPartList:id,code,name,part_type,content_type,content,description,org_code,weight']
            )->first()
        ) {
            throw new ServiceException("数据{$id}不存在");
        }
        return $result;
    }

    /**
     * @param array $data
     * @param int                         $id
     *
     * @return mixed
     * @throws ServiceException
     */
    public function update(array $data, int $id = 0)
    {
        if (empty(
            $result = LowCodeTemplate::query()->where('id', $id)->first()
        )
        ) {
            throw new ServiceException("数据{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts($data,LowCodeTemplate::class);
        $templateCode = $result->code;
        $this->clearCache($templateCode);
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
        if (!$result = LowCodeTemplate::query()->where('id', $id)->first()) {
            throw new ServiceException("ID:{$id}不存在");
        }
        $templateCode = $result->code;
        if ($result->delete()){
            $this->clearCache($templateCode);
            return true;
        }
        return false;
    }

    /**
     * @param array $partCodes
     * @param string $templateCode
     * @param array $lockedPartCodes
     *
     * @return true
     * @throws ServiceException
     */
    public function bindPart(array $partCodes, string $templateCode,
        array $lockedPartCodes = [],
    ) {
        $template = LowCodeTemplate::query()->where('code', $templateCode)
            ->first(['id', 'code']);

        if (!$template) {
            throw new ServiceException("模板编码:{$templateCode} 不存在");
        }

        // 验证 lockedPartCodes 必须是 partCodes 的子集
        $diff = array_diff($lockedPartCodes, $partCodes);
        if (!empty($diff)) {
            throw new ServiceException(
                "锁定零件集合中，中包含未绑定的零件: ".implode(', ', $diff)
            );
        }

        // 零件存在校验
        if (LowCodePart::query()->whereIn('code', $partCodes)->count()
            !== count($partCodes)
        ) {
            throw new ServiceException("零件编码不匹配，请检查");
        }

        //构建 sync 数据：locked 优先级 = 传入 locked > 已锁
        $syncData = [];
        foreach ($partCodes as $key => $code) {
            $syncData[$code] = ['locked' => in_array($code, $lockedPartCodes)
                ? 1 : 0,];
            $syncData[$code] = ['weight' => ((++$key) * 10)];
        }
        //执行同步 并刷新缓存
        try {
            //安全代理无法使用事物
            match (config('app.env')) {
                'production' => DB::transaction(
                    fn () => $this->syncTemplateParts($template, $syncData, $templateCode)
                ),
                default => $this->syncTemplateParts($template, $syncData, $templateCode),
            };
        } catch (\Exception $exception) {

        }
        return true;
    }

    /**
     * 同步模板零件
     * @param $template
     * @param $syncData
     * @param $templateCode
     *
     * @return void
     */
    protected function syncTemplateParts($template, $syncData, $templateCode): void
    {
        $template->bindPartList()->sync($syncData);
        $listCodes = LowCodeList::query()->orwhere(
            'template_code_filter', $templateCode
        )->orWhere('template_code_field', $templateCode)->orWhere(
            'template_code_column', $templateCode
        )->orWhere('template_code_button', $templateCode)->orWhere(
            'template_code_top_button', $templateCode
        )->pluck('code');
        foreach ($listCodes as $code) {
            TemplatePartCacheManager::clearListCache($code);
        }
        TemplatePartCacheManager::clearTemplatePartsCache(
            $templateCode
        );
    }

    /**
     * 清理缓存
     * @param string $templateCode
     *
     * @return void
     */
    public function clearCache(string $templateCode = '')
    {
        $listCodes = LowCodeList::query()->orwhere(
            'template_code_filter', $templateCode
        )->orWhere('template_code_field', $templateCode)->orWhere(
            'template_code_column', $templateCode
        )->orWhere('template_code_button', $templateCode)->orWhere(
            'template_code_top_button', $templateCode
        )->pluck('code');

        foreach ($listCodes as $code) {
            TemplatePartCacheManager::clearListCache($code);
        }
        TemplatePartCacheManager::clearTemplatePartsCache(
            $templateCode
        );
    }
}
