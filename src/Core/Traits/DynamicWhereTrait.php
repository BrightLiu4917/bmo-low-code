<?php

namespace BrightLiu\LowCode\Core\Traits;

use Closure;
use Illuminate\Support\Arr;
use App\Models\LowCode\LowCodeList;

/**
 * 混合查询
 */
trait DynamicWhereTrait
{
    public function whereMixed(array $conditions,
        string $defaultBoolean = 'and',
    ): self {
        foreach ($conditions as $key => $condition) {
            // 处理 ['or', Closure] 或 ['and', Closure]
            if (is_array($condition) && count($condition) === 2 &&
                in_array(strtolower(Arr::first($condition)), ['or', 'and']) &&
                $condition[1] instanceof Closure) {
                $this->queryBuilder = $this->queryBuilder->where(fn ($query,
                ) => $condition[1]($query), null, null,
                    strtolower(Arr::first($condition)));
                continue;
            }

            // 普通 and 间的闭包条件
            if ($condition instanceof Closure) {
                $this->queryBuilder = $this->queryBuilder->where(fn ($query,
                ) => $condition($query), null, null, $defaultBoolean);
                continue;
            }

            //支持raw原生
            if (is_array($condition) && isset($condition[0]) &&
                strtolower($condition[0]) === 'raw') {
                $rawValue = $condition[1];
                $this->queryBuilder = is_array($rawValue) ?
                    $this->queryBuilder->whereRaw(Arr::first($rawValue),
                        data_get($rawValue, 1) ?? []) :
                    $this->queryBuilder->whereRaw($rawValue);
                continue;
            }

            // 普通键值对 ['column' => 'value']
            if (!is_array($condition)) {
                $this->queryBuilder = $this->queryBuilder->where($key, '=',
                    $condition, $defaultBoolean);
                continue;
            }

            // 关联数组写法 [['name' => 'John']]
            if (Arr::isAssoc($condition)) {
                foreach ($condition as $col => $val) {
                    $this->queryBuilder = $this->queryBuilder->where($col, '=',
                        $val, $defaultBoolean);
                }
                continue;
            }

            // 解析 ['or', column, operator, value] / [column, operator, value]
            [$boolean, $column, $operator, $value]
                = $this->normalizeCondition($condition, $defaultBoolean);

            match (strtolower($operator)) {
                'in'          => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhereIn' :
                    'whereIn'}($column, (array)$value),
                'not in'      => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhereNotIn' :
                    'whereNotIn'}($column, (array)$value),
                'between'     => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ?
                    'orWhereBetween' : 'whereBetween'}($column, (array)$value),
                'not between' => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ?
                    'orWhereNotBetween' : 'whereNotBetween'}($column,
                    (array)$value),
                'is'          => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhereNull' :
                    'whereNull'}($column),
                'is not'      => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ?
                    'orWhereNotNull' : 'whereNotNull'}($column),
                'like'        => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhere' :
                    'where'}($column, 'like', "%$value%"),
                'not like'    => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhere' :
                    'where'}($column, 'not like', "%$value%"),
                default       => $this->queryBuilder
                    = $this->queryBuilder->{$boolean === 'or' ? 'orWhere' :
                    'where'}($column, $operator, $value),
            };
        }

        return $this;
    }

    /**
     * 规范化条件数组的格式。
     *
     * 该函数根据输入的条件数组的长度，将其转换为统一的格式。支持的输入格式包括：
     * - 长度为2的数组：[字段名, 值]，默认使用 'and' 作为布尔运算符，并添加 '=' 作为比较运算符。
     * - 长度为3的数组：[字段名, 比较运算符, 值]，默认使用 'and' 作为布尔运算符。
     * - 长度为4的数组：[布尔运算符, 字段名, 比较运算符, 值]，布尔运算符必须为 'or' 或 'and'。
     *
     * @param array  $condition      输入的条件数组，长度必须为2、3或4。
     * @param string $defaultBoolean 默认的布尔运算符，默认为 'and'。
     *
     * @return array 返回规范化后的条件数组，格式为 [布尔运算符, 字段名, 比较运算符, 值]。
     * @throws \InvalidArgumentException 如果输入的条件数组长度不符合要求，抛出异常。
     */
    protected function normalizeCondition(array $condition,
        string $defaultBoolean = 'and',
    ): array {
        $length = count($condition);

        // 处理长度为2的条件数组，添加默认的布尔运算符和比较运算符
        if ($length === 2) {
            return [$defaultBoolean, $condition[0], '=', $condition[1]];
        }

        // 处理长度为3的条件数组，添加默认的布尔运算符
        if ($length === 3) {
            return [
                $defaultBoolean, $condition[0], mb_strtolower($condition[1]),
                $condition[2],
            ];
        }

        // 处理长度为4的条件数组，验证布尔运算符是否为 'or' 或 'and'
        if ($length === 4 &&
            in_array(mb_strtolower($condition[0]), ['or', 'and'])) {
            return [
                mb_strtolower($condition[0]), $condition[1],
                mb_strtolower($condition[2]), $condition[3],
            ];
        }

        // 如果条件数组长度不符合要求，抛出异常
        throw new \InvalidArgumentException(sprintf('Invalid where condition format. Expected array length 2, 3, or 4, got %d. Condition: %s',
                $length, json_encode($condition)));
    }

    /**
     * 添加主键条件
     *
     * @param string $value      主键值
     * @param string $primaryKey 主键字段名
     *
     * @return self
     */
    public function wherePrimaryKey(string $value = '',
        string $primaryKey = 'user_id',
    ): self {
        $this->queryBuilder
            = $this->queryBuilder->where([$primaryKey => $value]);
        return $this;
    }


    /**
     * 添加ID条件
     *
     * @param string $value
     *
     * @return $this
     */
    public function whereId(string $value = ''): self
    {
        return $this->whereField($value, 'id');
    }

    /**
     * 添加用户ID条件
     *
     * @param string $value 用户ID值
     *
     * @return self
     */
    public function whereUserId(string $value = ''): self
    {
        return $this->whereField($value, 'user_id');
    }

    /**
     * @param string $value
     *
     * @return \BrightLiu\LowCode\Core\Traits\DynamicWhereTrait|\BrightLiu\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function whereEmpi(string $value = ''): self
    {
        return $this->whereField($value, 'empi');
    }

    /**
     * 纳管机构
     * @param string $value
     *
     * @return DynamicWhereTrait|\App\Support\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function whereManageOrgCode(string $value = ''): self
    {
        return $this->whereField($value, 'incprt_into_mng_org_cd');
    }

    /**
     * 添加身份证号条件
     *
     * @param string $value 身份证号值
     *
     * @return self
     */
    public function whereIdCrdNo(string $value = ''): self
    {
        return $this->whereField($value, 'id_crd_no');
    }


    /**
     * 纳入管表示
     *
     * @param string $value
     *
     * @return DynamicWhereTrait|\App\Support\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function whereManageFlag(string $value = '1'): self
    {
        return $this->whereField($value, 'incprt_into_mng_flg');
    }

    /**
     * 添加批量身份证号条件
     *
     * @param array $value 身份证号数组
     *
     * @return self
     */
    public function whereBatchIdCrdNos(array $value = []): self
    {
        $this->queryBuilder = $this->queryBuilder->whereIn('id_crd_no', $value);
        return $this;
    }

    /**
     * 人群分类条件
     *
     * @param array $value
     *
     * @return DynamicWhereTrait|\App\Support\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function whereCrowdType(string $value = ''): self
    {
        return $this->whereField($value, 'ptt_crwd_clsf_cd');
    }

    /**
     * 添加批量用户ID条件
     *
     * @param array $value 用户ID数组
     *
     * @return self
     */
    public function whereBatchUserIds(array $value = []): self
    {
        $this->queryBuilder = $this->queryBuilder->whereIn('user_id', $value);
        return $this;
    }

    /**
     * 添加字段条件
     *
     * @param string $value 字段值
     * @param string $field 字段名
     *
     * @return static
     */
    public function whereField(string $value, string $field = 'user_id',
    ): static {
        $this->queryBuilder = $this->queryBuilder->where([$field => $value]);
        return $this;
    }

    /**
     * 添加模糊查询条件
     *
     * @param string $value 查询值
     * @param string $field 字段名
     *
     * @return self
     */
    protected function whereLike(string $value, string $field = 'user_id'): self
    {
        $this->queryBuilder = $this->queryBuilder->where($field, 'like',
            "%{$value}%");
        return $this;
    }

    /**
     * 预设条件
     * @param string $listCode
     *
     * @return \BrightLiu\LowCode\Core\Traits\DynamicWhereTrait|\BrightLiu\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function whereListPresetCondition(string $listCode): self
    {
        if (empty($listCode)) {
            return $this;
        }

        $condition = LowCodeList::query()->where('code', $listCode)->value('preset_condition_json');

        if (!empty($condition)) {
            $this->whereMixed($condition);
        }

        return $this;
    }
}
