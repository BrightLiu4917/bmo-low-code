<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 行政区划本地化配置
    |--------------------------------------------------------------------------
    |
    | 业务说明：各项目可根据所在区域自行配置默认的行政区划数据。
    |
    |【字段说明】
    | - value & code : 行政区划唯一编码
    | - label & name : 行政区划标准名称
    | - parent_code  : 父级行政区划编码
    | - level        : 数字层级标识 (1=省级, 2=市级, 3=区县级, 4=乡镇级, 5=村级)
    | - type         : level 的英文别名 (province=省级, city=市级, district=区县级, town=乡镇级, community=村级)
    |
    |【参考 SQL】
    | 如需设置自己项目所在区域的配置，可参考以下 SQL 逻辑：
    |
    | SELECT
    |     prm_key         AS value,
    |     prm_key         AS code,
    |     admnstrt_rgn_nm AS label,
    |     admnstrt_rgn_nm AS name,
    |     pre_cd          AS parent_code,
    |     lvl_flg         AS level,
    |     CASE lvl_flg
    |         WHEN 1 THEN 'province'
    |         WHEN 2 THEN 'city'
    |         WHEN 3 THEN 'district'
    |         WHEN 4 THEN 'town'
    |         WHEN 5 THEN 'community'
    |         ELSE 'unknown'
    |     END             AS type
    | FROM
    |     core_knlg.mdm_admnstrt_rgn_y
    | WHERE
    |     invld_flg = 0
    |     AND admnstrt_rgn_nm <> '市辖区'
    |     AND prm_key like '4602%' -- 4602 是三亚的开头编码，替换为自己的
    |
    */

    // 示例配置
    // '460200000000' => [
    //     "value"       => "460200000000",
    //     "code"        => "460200000000",
    //     "label"       => "三亚市",
    //     "name"        => "三亚市",
    //     "parent_code" => "460000000000",
    //     "level"       => "2",
    //     "type"        => "city"
    // ],
];