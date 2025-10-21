# low-code
### 安装 Composer 包
```text 
composer require bright-liu4917/low-code:v1.0.0
```
### 在 config/app.php配置文件中注册服务提供者：
```text
'providers' => [
    // 其他服务提供者...
    BrightLiu\LowCode\Providers\LowCodeServiceProvider::class,
],
```
### 发布配置文件
```text
php artisan vendor:publish --provider="BrightLiu\LowCode\Providers\LowCodeServiceProvider" --tag="low-code-config"
```

### 文件初始化
#### 快速初始化 (初始化所有核心文件（不包含模型)
```text
php artisan lowcode:init --f
```
#### 分步初始化
```text
# 1. 创建缓存观察者 --f：强制覆盖现有文件
php artisan lowcode:install-cache-observer --f

# 2. 创建列表控制器 --f：强制覆盖现有文件
php artisan lowcode:install-list-controller --f

# 3. 创建查询引擎 --f：强制覆盖现有文件
php artisan lowcode:install-query-engine --f

# 4. 创建列表查询资源 --f：强制覆盖现有文件
php artisan lowcode:install-list-query-resource --f
```
#### 模型文件创建
```text
# 从包中复制模型文件到项目 --f：强制覆盖现有文件
php artisan lowcode:copy-models bright-liu4917/low-code --f
```



### 使用方式
#### 路由
```text
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LowCode\LowCodeListController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodePartController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeTemplateController;
use BrightLiu\LowCode\Controllers\Disease\DiseaseController;
use BrightLiu\LowCode\Controllers\DatabaseSource\DatabaseSourceController;
use App\Http\Controllers\LowCode\LowCodePersonalizeModuleController;
use App\Http\Controllers\LowCode\LowCodeListV2Controller;
use BrightLiu\LowCode\Controllers\LowCode\ResidentCrowdController;

/**
** Route::group(['prefix' => 'v2/resident', 'middleware' => ['bmp.disease.auth.inner']], function () {
    ** Route::get('resident-crowd/optional', [ResidentCrowdController::class, 'optional']);
** });
**/

Route::group([
    'middleware' => ['auth.disease'],//登陆中间件
], function () {

    // 列表v1版本
    Route::prefix('v1/low-code/list')->group(function () {
        Route::get('list', [LowCodeListController::class, 'list']);
        Route::get('show', [LowCodeListController::class, 'show']);
        Route::post('simple-list', [LowCodeListController::class, 'simpleList']);
        Route::post('update', [LowCodeListController::class, 'update']);
        Route::post('delete', [LowCodeListController::class, 'delete']);
        Route::post('query', [LowCodeListController::class, 'query']);
        Route::post('pre', [LowCodeListController::class, 'pre']);
    });

    // 个性化模板
    Route::prefix('v2/foundation/personalize-module')->group(function () {
        Route::get('list', [LowCodePersonalizeModuleController::class, 'list']);
        Route::get('routes', [LowCodePersonalizeModuleController::class, 'routes']);
        Route::post('save', [LowCodePersonalizeModuleController::class, 'save']);
    });

    // 列表v2版本
    Route::prefix('v2/low-code/list')->group(function () {
        Route::get('simple-list', [LowCodeListV2Controller::class, 'simpleList']);
        Route::post('query', [LowCodeListV2Controller::class, 'query']);
        Route::post('pre', [LowCodeListV2Controller::class, 'pre']);
        Route::post('query-count', [LowCodeListV2Controller::class, 'queryCount']);
        Route::get('optional-columns', [LowCodeListV2Controller::class, 'optionalColumns']);
        Route::get('get-column-preference', [LowCodeListV2Controller::class, 'getColumnPreference']);
        Route::post('update-column-preference', [LowCodeListV2Controller::class, 'updateColumnPreference']);

    });

    // 列表部件
    Route::prefix('v1/low-code/part')->group(function () {
        Route::get('list', [LowCodePartController::class, 'list']);
        Route::post('create', [LowCodePartController::class, 'create']);
        Route::get('show', [LowCodePartController::class, 'show']);
        Route::post('update', [LowCodePartController::class, 'update']);
        Route::post('delete', [LowCodePartController::class, 'delete']);
        Route::get('get-table-fields', [LowCodePartController::class, 'getTableFields']);
    });

    // 列表模板
    Route::prefix('v1/low-code/template')->group(function () {
        Route::get('list', [LowCodeTemplateController::class, 'list']);
        Route::get('show', [LowCodeTemplateController::class, 'show']);
        Route::post('update', [LowCodeTemplateController::class, 'update']);
        Route::post('delete', [LowCodeTemplateController::class, 'delete']);
    });
    Route::post('v1/low-code/template-bind-parts', [LowCodeTemplateController::class, 'bindPart']);

    //病种
    Route::prefix('v1/disease')->group(function () {
        Route::get('list', [DiseaseController::class, 'list']);
        Route::get('show', [DiseaseController::class, 'show']);
        Route::post('update', [DiseaseController::class, 'update']);
        Route::post('delete', [DiseaseController::class, 'delete']);
    });
    //数据源
    Route::prefix('v1/database-source')->group(function () {
        Route::get('list', [DatabaseSourceController::class, 'list']);
        Route::get('show', [DatabaseSourceController::class, 'show']);
        Route::post('update', [DatabaseSourceController::class, 'update']);
        Route::post('delete', [DatabaseSourceController::class, 'delete']);
    });
});

```

#### innerapi
```text
<?php
use BrightLiu\LowCode\Controllers\LowCode\InitOrgDiseaseController;

Route::post('v2/init/org-disease', InitOrgDiseaseController::class)
    ->middleware(['bmp.disease.auth.inner']);
});
```
#### app/Http/Kernel.php 追加参数
```text
$routeMiddleware = [
        'auth.disease' => DiseaseAuthenticate::class,
        'bmp.disease.auth.inner' => BmpDiseaseAuthenticateInner::class
]
```

#### 日志
```text
    在 config/logging.php配置文件中注册服务提供者：
    
    'low-code-list' => 
        [
            'driver' => 'daily',
            'path' => storage_path('logs/low-code-list/daily.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
```

### 初始化数据库
```text
# 数据库表机构
```mysql
CREATE TABLE `low_code_lists`
(
    `id`                       bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `data_permission_code`     varchar(64) NOT NULL DEFAULT '' COMMENT '数据权限编码',
    `disease_code`             varchar(32)         NOT NULL DEFAULT '' COMMENT '病种编码',
    `code`                     varchar(64)         NOT NULL DEFAULT '' COMMENT '列表编码',
    `parent_code`              varchar(64)         NOT NULL DEFAULT '' COMMENT '上级列表编码',
    `org_code`                 varchar(64)         NOT NULL DEFAULT '' COMMENT '机构编码',
    `admin_name`               varchar(64)         NOT NULL DEFAULT '' COMMENT '专病后台列表名字',
    `family_doctor_name`       varchar(64)         NOT NULL DEFAULT '' COMMENT '家庭医生列表名字',
    `mobile_doctor_name`       varchar(64)         NOT NULL DEFAULT '' COMMENT '移动医生列表名字',
    `admin_weight`             int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '专病后台权重(降序)',
    `family_doctor_weight`     int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '家庭医生权重(降序)',
    `mobile_doctor_weight`     int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '移动医生权重(降序)',
    `crowd_type_code`          varchar(64)         NOT NULL DEFAULT '' COMMENT '人群类型编码',
    `template_code_filter`     varchar(64)         NOT NULL DEFAULT '' COMMENT '模板-筛选',
    `template_code_column`     varchar(64)         NOT NULL DEFAULT '' COMMENT '模板-表头',
    `template_code_field`      varchar(64)         NOT NULL DEFAULT '' COMMENT '模板-查询字段',
    `template_code_button`     varchar(64)         NOT NULL DEFAULT '' COMMENT '模板-操作按钮',
    `template_code_top_button` varchar(64)         NOT NULL DEFAULT '' COMMENT '模板-顶部操作按钮',
    `route_group`              json                         DEFAULT NULL COMMENT '前端路由组',
    `append_field_json`        json                         DEFAULT NULL COMMENT '追加查询字段，模板少了',
    `append_column_json`       json                         DEFAULT NULL COMMENT '追加表头，模板少了',
    `append_filter_json`       json                         DEFAULT NULL COMMENT '追加筛选条件，模板少了',
    `append_button_json`       json                         DEFAULT NULL COMMENT '追加按钮，模板少了',
    `append_top_button_json`   json                         DEFAULT NULL COMMENT '追加顶部按钮，模板少了',
    `remove_field_json`        json                         DEFAULT NULL COMMENT '移除查询字段，模板多了',
    `remove_filter_json`       json                         DEFAULT NULL COMMENT '移除筛选条件，模板多了',
    `remove_column_json`       json                         DEFAULT NULL COMMENT '移除表头，模板多了',
    `remove_button_json`       json                         DEFAULT NULL COMMENT '移除筛选条件，模板多了',
    `remove_top_button_json`   json                         DEFAULT NULL COMMENT '移除表头，模板多了',
    `default_order_by_json`    json                         DEFAULT NULL COMMENT '默认排序字段',
    `preset_condition_json`    json                         DEFAULT NULL COMMENT '预设条件',
    `list_type`                tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '列表类型0默认，9通用（适配多个人群分类）不可删除',
    `creator_id`               bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `updater_id`               bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
    `created_at`               datetime                     DEFAULT NULL COMMENT '创建时间',
    `updated_at`               datetime                     DEFAULT NULL COMMENT '更新时间',
    `deleted_at`               datetime                     DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`),
    KEY `idx_disease_code` (`disease_code`),
    KEY `idx_org_code` (`org_code`),
    KEY `idx_crowd_type_code` (`crowd_type_code`),
    KEY `idx_creator_id` (`creator_id`),
    KEY `idx_updater_id` (`updater_id`),
    KEY `idx_template_code_filter` (`template_code_filter`),
    KEY `idx_template_code_field` (`template_code_field`),
    KEY `idx_template_code_column` (`template_code_column`),
    KEY `template_code_button` (`template_code_button`),
    KEY `idx_template_code_top_button` (`template_code_top_button`),
    KEY `idx_disease_code_org_code` (`disease_code`(16), `org_code`(16)),
    KEY `idx_partent_code` (`parent_code`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='低代码-列表';

CREATE TABLE `low_code_parts`
(
    `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`         varchar(64)         NOT NULL DEFAULT '' COMMENT '名字',
    `code`         varchar(64)         NOT NULL DEFAULT '' COMMENT '唯一编码',
    `org_code`     varchar(64)         NOT NULL DEFAULT '' COMMENT '机构编码',
    `part_type`    int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '零件类型 1系统内置,2客户自定义',
    `description`  varchar(200)        NOT NULL DEFAULT '' COMMENT '描述',
    `content_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型:1列头展示，2筛选项，3操作栏按钮，4顶部按钮，5查询字段集合',
    `content`      json                         DEFAULT NULL COMMENT '组件内容',
    `weight`       int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '权重(降序)',
    `creator_id`   bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `updater_id`   bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
    `created_at`   datetime                     DEFAULT NULL COMMENT '创建时间',
    `updated_at`   datetime                     DEFAULT NULL COMMENT '更新时间',
    `deleted_at`   datetime                     DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`),
    KEY `idx_content_type` (`content_type`),
    KEY `idx_org_code` (`org_code`),
    KEY `idx_creator_id` (`creator_id`),
    KEY `idx_updater_id` (`updater_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='低代码-组件（零件）';

/******************************************/
/*   DatabaseName = ih_medication_sanya   */
/*   TableName = low_code_templates   */
/******************************************/
CREATE TABLE `low_code_templates` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
                                      `name` varchar(64) NOT NULL DEFAULT '' COMMENT '名字',
                                      `disease_code` varchar(64) NOT NULL DEFAULT '' COMMENT '疾病编码',
                                      `code` varchar(64) NOT NULL DEFAULT '' COMMENT '唯一编码',
                                      `org_code` varchar(64) NOT NULL DEFAULT '' COMMENT '机构编码',
                                      `template_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '模板类型:1通用的已纳管,2通用的待纳管,3通用的推荐纳管,4通用的出组',
                                      `content_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '内容类型:1列头展示，2筛选项，3操作栏按钮，4顶部按钮，5查询字段集合',
                                      `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
                                      `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权重(降序)',
                                      `creator_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
                                      `updater_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
                                      `created_at` datetime DEFAULT NULL COMMENT '创建时间',
                                      `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
                                      `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `unique_code` (`code`),
                                      KEY `idx_content_type` (`content_type`),
                                      KEY `idx_disease_code` (`disease_code`),
                                      KEY `idx_org_code` (`org_code`),
                                      KEY `idx_disease_code_org_code` (`disease_code`(16),`org_code`(16)),
                                      KEY `idx_template_type` (`template_type`),
                                      KEY `idx_creator_id` (`creator_id`),
                                      KEY `idx_updater_id` (`updater_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='低代码-组件（模板）';

CREATE TABLE `low_code_template_has_parts` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
                                               `part_code` varchar(64) NOT NULL DEFAULT '' COMMENT '零件唯一编码',
                                               `template_code` varchar(64) NOT NULL DEFAULT '' COMMENT '模板唯一编码',
                                               `locked` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否锁定字段：1锁定,0未锁定',
                                               `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权重(降序)',
                                               `created_at` datetime DEFAULT NULL COMMENT '创建时间',
                                               `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
                                               `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
                                               PRIMARY KEY (`id`),
                                               KEY `idx_part_code` (`part_code`),
                                               KEY `idx_template_code` (`template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='低代码-模板和组件的关系表';


CREATE TABLE `database_sources` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
                                    `disease_code` varchar(32) NOT NULL DEFAULT '' COMMENT '病种编码',
                                    `code` varchar(32) NOT NULL DEFAULT '' COMMENT '编码',
                                    `name` varchar(64) NOT NULL DEFAULT '' COMMENT '名字',
                                    `host` varchar(64) NOT NULL DEFAULT '' COMMENT '主机地址',
                                    `database` varchar(64) NOT NULL DEFAULT '' COMMENT '数据库',
                                    `table` varchar(64) NOT NULL DEFAULT '' COMMENT '表',
                                    `port` varchar(64) NOT NULL DEFAULT '' COMMENT '端口',
                                    `username` varchar(255) NOT NULL DEFAULT '' COMMENT '账号',
                                    `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
                                    `options` json DEFAULT NULL COMMENT '扩展项',
                                    `source_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '数据源类型:1数据,2业务',
                                    `creator_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
                                    `updater_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
                                    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
                                    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
                                    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
                                    PRIMARY KEY (`id`),
                                    UNIQUE KEY `unique_code` (`code`),
                                    KEY `idx_creator_id` (`creator_id`),
                                    KEY `idx_updater_id` (`updater_id`),
                                    KEY `idx_disease_code` (`disease_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据库源表';

/******************************************/
/*   DatabaseName = ih_medication_sanya   */
/*   TableName = pa_admin_preferences   */
/******************************************/
CREATE TABLE `pa_admin_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `disease_code` varchar(32) NOT NULL DEFAULT '' COMMENT '病种编码',
  `org_code` varchar(32) NOT NULL DEFAULT '' COMMENT '所属机构编码',
  `admin_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '管理员id',
  `scene` varchar(32) NOT NULL DEFAULT '' COMMENT '场景',
  `pkey` varchar(64) NOT NULL DEFAULT '' COMMENT '偏好键',
  `pvalue` json DEFAULT NULL COMMENT '偏好值',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_disease_code_org_code` (`disease_code`(8),`org_code`(16))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员-偏好设置'
;

CREATE TABLE `personalize_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `disease_code` varchar(32) NOT NULL DEFAULT '' COMMENT '病种编码',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '标题',
  `module_id` varchar(32) NOT NULL DEFAULT '' COMMENT '模块ID',
  `module_type` varchar(32) NOT NULL DEFAULT '' COMMENT '模块类型',
  `metadata` json DEFAULT NULL COMMENT '元数据',
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权重(降序)',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_disease_code_org_code` (`disease_code`(8)),
  KEY `idx_module_id` (`module_id`(8))
) ENGINE=InnoDB  COMMENT='个性化模块表';
```

```text
AppServiceProvider.register() 追加以下代码

use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Context\OrgContext;


    public function register()
    {
        $this->app->singleton('context:org', OrgContext::class);
        $this->app->singleton('context:auth', AuthContext::class);
        $this->app->singleton('context:disease', DiseaseContext::class);
        //其他代码
    }
     
     获取用户当前信息
     auth()->user();
     
     //初始化病种用户信息等 中间件 DiseaseAuthenticate
     App\Http.Kernel.$routeMiddleware = [
         'auth.disease' => DiseaseAuthenticate::class
     ]
     
     创建文件 并配置用户中心请求地址
     config('business.bmo-service.auth.base_uri',env('BMO_AUTH_BASE_URI')) 
     
     创建文件 并配置业务中台请求地址
     //有可能 第一个请求 java 第二请求另一个java 所以分开
     config('business.api-service.bmp_cheetah_medical_crowdkit.uri', env('BMP_CHEETAH_MEDICAL_CROWDKIT_URI'))
     config('business.api-service.bmp_cheetah_medical_platform.uri', env('BMP_CHEETAH_MEDICAL_CROWDKIT_URI'))
     
     
     

     
     
     
    Env 添加
    BMP_CHEETAH_MEDICAL_CROWDKIT_URI=业务中台api
    BMO_AUTH_BASE_URI=用户中心api


    //基线表配置
    DB_MEDICAL_PLATFORM_CONNECTION=mysql
    DB_MEDICAL_PLATFORM_HOST={}
    DB_MEDICAL_PLATFORM_PORT=3306
    DB_MEDICAL_PLATFORM_DATABASE={}
    DB_MEDICAL_PLATFORM_USERNAME={}
    DB_MEDICAL_PLATFORM_PASSWORD={}
    DB_MEDICAL_PLATFORM_CONNECTION_TIMEOUT=10
    DB_MEDICAL_PLATFORM_PREPARES=false
```
#### 将该文件 命名 templates.json 并且放到 storage 下
##### 该文件内容随意更改
chmod -R 777 storage
```json
{
    "normal": [
        {
            "list": [
                {
                    "admin_name": "全部",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "待纳管",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "raw",
                            "(biz_mng_flg is null or biz_mng_flg = 0)"
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "评估中",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "biz_mng_flg",
                            "=",
                            2
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "方案制定中",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "biz_mng_flg",
                            "in",
                            [3,4]
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "管理中",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "biz_mng_flg",
                            "=",
                            5
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "疗程结束",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "biz_mng_flg",
                            "=",
                            6
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "方案终止",
                    "route_group": [
                        "/Patient/ManagedPatient"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ],
                        [
                            "biz_mng_flg",
                            "=",
                            9
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0
                },
                {
                    "admin_name": "人群患者列表",
                    "route_group": [
                        "/Crowd/Patients"
                    ],
                    "preset_condition_json": [
                        [
                            "is_deleted",
                            "=",
                            0
                        ]
                    ],
                    "default_order_by_json": [
                        [
                            "gmt_created",
                            "desc"
                        ],
                        [
                            "user_id",
                            "desc"
                        ]
                    ],
                    "list_type": 0,
                    "templates": {
                        "filter": {
                            "name": "通用列表-筛选字段",
                            "description": "通用列表-筛选字段",
                            "template_type": 1,
                            "content_type": 2,
                            "parts": [
                                {
                                    "name": "患者:姓名(筛选)",
                                    "description": "患者:姓名(筛选)",
                                    "content": {
                                        "key": "rsdnt_nm",
                                        "type": "input",
                                        "field": "rsdnt_nm",
                                        "label": "患者姓名",
                                        "props": {
                                            "options": [],
                                            "labelField": null,
                                            "valueField": null,
                                            "childrenField": null
                                        },
                                        "apiUrl": null,
                                        "search": "like",
                                        "showLabel": null,
                                        "placeholder": "请输入患者姓名"
                                    }
                                },
                                {
                                    "name": "患者:身份证(筛选)",
                                    "description": "患者:身份证(筛选)",
                                    "content": {
                                        "key": "id_crd_no",
                                        "type": "input",
                                        "field": "id_crd_no",
                                        "label": "患者身份证",
                                        "props": {
                                            "options": [],
                                            "labelField": null,
                                            "valueField": null,
                                            "childrenField": null
                                        },
                                        "apiUrl": null,
                                        "search": "like",
                                        "showLabel": null,
                                        "placeholder": "请输入患者身份证"
                                    }
                                },
                                {
                                    "name": "患者:电话号码(筛选)",
                                    "description": "患者:电话号码(筛选)",
                                    "content": {
                                        "key": "slf_tel_no",
                                        "type": "input",
                                        "field": "slf_tel_no",
                                        "label": "患者电话号码",
                                        "props": {
                                            "options": [],
                                            "labelField": null,
                                            "valueField": null,
                                            "childrenField": null
                                        },
                                        "apiUrl": null,
                                        "search": "like",
                                        "showLabel": null,
                                        "placeholder": "请输入患者电话号码"
                                    }
                                }
                            ]
                        }
                    }
                }
            ],
            "templates": {
                "column": {
                    "name": "通用列表-展示字段",
                    "description": "通用列表-展示字段",
                    "template_type": 1,
                    "content_type": 1,
                    "parts": [
                        {
                            "name": "患者:姓名",
                            "description": "患者姓名",
                            "content": {
                                "key": "rsdnt_nm",
                                "fixed": true,
                                "title": "姓名"
                            }
                        },
                        {
                            "name": "患者:身份证",
                            "description": "患者身份证",
                            "content": {
                                "key": "id_crd_no",
                                "title": "身份证"
                            }
                        },
                        {
                            "name": "患者:电话号码",
                            "description": "患者电话号码",
                            "content": {
                                "key": "slf_tel_no",
                                "title": "电话号码"
                            }
                        },
                        {
                            "name": "患者:状态",
                            "description": "患者管理状态",
                            "content": {
                                "key": "biz_mng_flg",
                                "title": "状态"
                            }
                        },
                        {
                            "name": "患者:人群分类",
                            "description": "患者人群分类",
                            "content": {
                                "key": "_crowds",
                                "title": "人群分类"
                            }
                        }
                    ]
                },
                "filter": {
                    "name": "通用列表-筛选字段",
                    "description": "通用列表-筛选字段",
                    "template_type": 1,
                    "content_type": 2,
                    "parts": [
                        {
                            "name": "患者:人群分类(筛选)",
                            "description": "患者:人群分类(筛选)",
                            "content": {
                                "key": "_crowds",
                                "type": "select",
                                "field": "crowd_id",
                                "label": "人群分类",
                                "props": {},
                                "apiUrl": "/api/v2/resident/resident-crowd/optional",
                                "search": "=",
                                "showLabel": null,
                                "placeholder": "请选择人群分类"
                            }
                        },
                        {
                            "name": "患者:姓名(筛选)",
                            "description": "患者:姓名(筛选)",
                            "content": {
                                "key": "rsdnt_nm",
                                "type": "input",
                                "field": "rsdnt_nm",
                                "label": "患者姓名",
                                "props": {
                                    "options": [],
                                    "labelField": null,
                                    "valueField": null,
                                    "childrenField": null
                                },
                                "apiUrl": null,
                                "search": "like",
                                "showLabel": null,
                                "placeholder": "请输入患者姓名"
                            }
                        },
                        {
                            "name": "患者:身份证(筛选)",
                            "description": "患者:身份证(筛选)",
                            "content": {
                                "key": "id_crd_no",
                                "type": "input",
                                "field": "id_crd_no",
                                "label": "患者身份证",
                                "props": {
                                    "options": [],
                                    "labelField": null,
                                    "valueField": null,
                                    "childrenField": null
                                },
                                "apiUrl": null,
                                "search": "like",
                                "showLabel": null,
                                "placeholder": "请输入患者身份证"
                            }
                        },
                        {
                            "name": "患者:电话号码(筛选)",
                            "description": "患者:电话号码(筛选)",
                            "content": {
                                "key": "slf_tel_no",
                                "type": "input",
                                "field": "slf_tel_no",
                                "label": "患者电话号码",
                                "props": {
                                    "options": [],
                                    "labelField": null,
                                    "valueField": null,
                                    "childrenField": null
                                },
                                "apiUrl": null,
                                "search": "like",
                                "showLabel": null,
                                "placeholder": "请输入患者电话号码"
                            }
                        }
                    ]
                },
                "button": {
                    "name": "通用列表-操作栏按钮",
                    "description": "通用列表-操作栏按钮",
                    "template_type": 1,
                    "content_type": 3,
                    "parts": [
                        {
                            "name": "患者:纳管(按钮)",
                            "description": "患者:纳管(按钮)",
                            "content": {
                                "key": "resident_manage",
                                "label": "去纳管",
                                "title": "去纳管",
                                "action": "MANAGE",
                                "visible": "Number(data.biz_mng_flg) == 0"
                            }
                        },
                        {
                            "name": "患者:评估(按钮)",
                            "description": "患者:评估(按钮)",
                            "content": {
                                "key": "resident_evaluation",
                                "label": "去评估",
                                "title": "去评估",
                                "action": "EVALUATION",
                                "visible": "Number(data.biz_mng_flg) == 2"
                            }
                        },
                        {
                            "name": "患者:制定方案(按钮)",
                            "description": "患者:制定方案(按钮)",
                            "content": {
                                "key": "resident_sop",
                                "label": "去制定",
                                "title": "去制定",
                                "action": "SOP",
                                "visible": "Number(data.biz_mng_flg) == 3 || Number(data.biz_mng_flg) == 4"
                            }
                        },
                        {
                            "name": "患者:任务日历(按钮)",
                            "description": "患者:任务日历(按钮)",
                            "content": {
                                "key": "resident_management_tasks",
                                "label": "任务日历",
                                "title": "任务日历",
                                "action": "MANAGEMENT_TASKS",
                                "visible": "Number(data.biz_mng_flg) == 5"
                            }
                        },
                        {
                            "name": "患者:制定新方案(按钮)",
                            "description": "患者:制定新方案(按钮)",
                            "content": {
                                "key": "resident_again_sop",
                                "label": "制定新方案",
                                "title": "制定新方案",
                                "action": "AGAIN_SOP",
                                "visible": "Number(data.biz_mng_flg) == 6 || Number(data.biz_mng_flg) == 9"
                            }
                        },
                        {
                            "name": "患者:专病档案(按钮)",
                            "description": "患者:专病档案(按钮)",
                            "content": {
                                "key": "resident_archives",
                                "label": "专病档案",
                                "title": "专病档案",
                                "action": "ARCHIVES",
                                "visible": "true"
                            }
                        }
                    ]
                }
            }
        }
    ]
}
```
### 执行 localhost/api/v1/org-disease 初始化 数据


### 创建 config('business.medical-platform') 追加以下内容

```text 
<?php

return [
    'data_warehouse' => [
        'default' => [
            'driver' => 'mysql',
            'host' => env('DB_MEDICAL_PLATFORM_HOST', '127.0.0.1'),
            'port' => env('DB_MEDICAL_PLATFORM_PORT', '3306'),
            'database' => env('DB_MEDICAL_PLATFORM_DATABASE', 'forge'),
            'username' => env('DB_MEDICAL_PLATFORM_USERNAME', 'forge'),
            'password' => env('DB_MEDICAL_PLATFORM_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_TIMEOUT => env('DB_MEDICAL_PLATFORM_CONNECTION_TIMEOUT', 10),
                PDO::ATTR_EMULATE_PREPARES => env('DB_MEDICAL_PLATFORM_PREPARES', false),
            ]) : [],
        ],
    ],
];
```