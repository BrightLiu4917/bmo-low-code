# 低代码包 bmo-low-code
### 安装 Composer 包
-----
```text
composer require bright-liu4917/bmo-low-code
```
-----
### 发布配置文件
```text
php artisan vendor:publish --provider="BrightLiu\LowCode\Providers\LowCodeServiceProvider"
```

 ### 执行填充数据 
```text
php artisan low-code:publish-data-permissions 
```
-----
### env配置文件
```text
#### 是否开启调试模式 开启后 执行初始化 “/innerapi/v2/init/org-disease” 不会执行事务
DEV_ENABLE=false

#### 用户中心 #####
BMO_ORG_ID=用户中心ID 用户中心 org_id 可以不写，预留的
BMO_APP_ID=用户中心 app_id
BMO_APP_SECRET=用户中心 app_secret
#### 用户中心 #####

#### 业务中台 #####
BMP_CHEETAH_MEDICAL_PLATFORM_URI=业务中台接口地址 宝庆老师
BMP_CHEETAH_MEDICAL_CROWD_KIT_URI=人群基线接口地址 童java
#### 业务中台 #####

#### 低代码配置 #####
#### 如果前端入参有"X-Gp-Scene-Code"参数 配置scene_code 否则 disease_code
#LOW_CODE_SET_USE_TABLE_FIELD = disease_code #默认disease_code or 不开启
#### 低代码配置 #####

#### 基线表 等配置 #####
DB_MEDICAL_PLATFORM_HOST
DB_MEDICAL_PLATFORM_PORT
DB_MEDICAL_PLATFORM_DATABASE
DB_MEDICAL_PLATFORM_USERNAME
DB_MEDICAL_PLATFORM_PASSWORD
DB_MEDICAL_CROWD_PSN_WDTH_TABLE=人员宽表一般是"crowd_psn_wdth" 问童java
DB_BUSINESS_CENTER_CROWD_TYPE_TABLE=患者标签关系表一般是 "feature_user_detail" 问童java
#### 基线表 等配置 #####


#### 地区#### 
DB_REGION_CONNECTION=mysql
DB_REGION_HOST=dphzmy-ztkrn3qkvmu6fbk9-pub.proxy.dms.aliyuncs.com
DB_REGION_PORT=3306
DB_REGION_DATABASE=core_knlg
DB_REGION_USERNAME=3ArpWTh77g35xSoGAW6gTf0o
DB_REGION_PASSWORD=2DsVhJkKEb6QuSEMszMdIKxjz0s1UP
DB_REGION_CONNECTION_TIMEOUT=10
DB_REGION_PREPARES=false
#### 地区#### 


#### AI 服务 找凡哥要 ####
BMO_AI_APP_ID
BMO_AI_APP_KEY
BMO_AI_BOT_ID
BMO_AI_APP_SECRET
BMO_AI_URI
BMO_AI_ENABLE=false# 是否开启AI
BMO_AI_CACHE_TTL=30# 结果缓存时间
BMO_AI_CACHE_ENABLE=false# 结果缓是否开启
```
-----

### 内置方法
```text
低代码查询数据
QueryEngineService::instance()
        ->autoClient()          //自动获取客户端入参数信息
        //useTable('$useTable') //强制更换表
        //->innerJoin()         //内置内联join leftJoin ...
        ->whereMixed(           // 设置查询条件 内置多种查询方法 whereUserId、 whereManageOrgCode、 whereIdCrdNo
                        [
                            ["ptt_crwd_clsf_cd", "=", "9efe2444eaf14606896bc68290abc5e7"],//模糊查询
                            ["ptt_nm", "like", "朱文奎f"],//模糊查询
                            ["crowd_id", "=", "330121196205038717f"]
                            // ["or", "id_crd_no", "like", "330121196205038717f"],//或查询
                            // ["ptt_nm", "in", ["active", "pending"]],//包含 查询
                            // ["or", "age", "not in", [18, 20]], //不包含
                            // ["slf_tel_no", "between", ["2023-01-01", "2023-12-31"]],//区间查询
                            // ["or", "slf_tel_no", "not between", [60, 80]],//不在区间
                            // ["slf_tel_no", "is", "null"],//是null
                            // ["slf_tel_no", "is not", "null"],//不是null
                            // ["raw", "slf_tel_no = 'active' AND slf_tel_no >= 90"]//原生sql
                        ],
        )
        ->setCache($ttl)                    //设置缓存时间
        ->orderBy([["id_crd_no", "asc"]])//排序
        ->groupBy(["fields"])
        ->select(["fields"])            //查询字段
        ->getCountResult()          //多个查询方法 内置多个查询方式
        

获取患者基础信息
ResidentService::instance()->getBasicInfo(empi:$empi)

获取患者完整信息
ResidentService::instance()->getInfo(empi:$empi)

更新患者信息
ResidentService::instance()->updateInfo(empi:$empi,attributes:['age'=>18])

纳管患者 相关参数 manage_org_code,manage_org_name,manage_doctor_code,manage_doctor_name 如不入参 会通过上下文获取
ResidentService::instance()->manageResident(empi:$empi,attributes:["相关参数"])  

出组患者 相关参数
ResidentService::instance()->removeManageResident(empi:$empi,attributes:['fields'],isClearManageData:true)  //isClearManageData 是否清理纳管相关参数

创建管理方案
ResidentService::instance()->createManagePlan(....)  

DataPermissionService::instance()
    ->channel($dataPermissionCode)//选择使用权限渠道 data_permissions.code 内容
//  ->setMappingField(['被替换的字段 如:manage_org_code'=>'业务所需字段 如:biz_org_code'])//映射业务字段
   ->run();
```
-----

### dependencies

通过配置 `low-code.dependencies` 项，重写包内部的处理逻辑，目前支持如下映射：

| 路由                                  | 源                                                                      | 说明             |
| ------------------------------------- | ----------------------------------------------------------------------- | ---------------- |
| api/v2/resident/resident-archive/info | \BrightLiu\LowCode\Resources\Resident\ResidentArchive\InfoResource::php | 居民档案详情数据 |
| api/v2/low-code/list/query            | \BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource::php       | 患者列表数据     |


#### QuerySource

```php
<?php

declare(strict_types=1);

use BrightLiu\LowCode\Resources\Resident\ResidentArchive\InfoResource;
use BrightLiu\LowCode\Support\Attribute\Converters\Age;
use BrightLiu\LowCode\Support\Attribute\Converters\IdCrdNo;
use BrightLiu\LowCode\Support\Attribute\Converters\SlfTelNo;

class BizInfoResource extends InfoResource
{
    protected function fetchConversion(): Conversion
    {
        // 可选为每个字段转换类，在其中处理值转换逻辑
        // 参考 BrightLiu\LowCode\Support\Attribute\Converters 中的内置转换类

        return Conversion::make([
            Age::class,
            IdCrdNo::class,
            SlfTelNo::class
        ]);
    }

    /**
     * 白名单
     * PS: 优先级高于黑名单
     */
    protected function fillable(): ?array
    {
        // PS: 只有在其中的字段才会返给前端，null时不限，默认为null。
        // - 优先级高于黑名单。

        return null;
    }

    /**
     * 黑名单
     */
    public function guarded(): ?array
    {
        // PS: 在其中的字段不会返给前端，null时不限，默认为null。
        // - 优先级低于黑名单。
        // - 当filleable方法存在有效值时，该方法无效。

        return null;
    }
}
```

#### InfoResource


### 注意事项
```text
1.
⚠️⚠️⚠️ api/v2/low-code/list/query 需要业务自己继承后，重新实现 主要是resource 返回数据结构 ⚠️⚠️⚠️

2.
通知前端必须header入参
X-Gp-Org-Id 机构ID 前端自己申请写死
X-Gp-System-Code 系统编码 研发PM定义 
X-Gp-Disease-Code 疾病编码 后端开发定义 比如 (sanya)
X-Gp-Scene-Code   场景编码 后端开发定义 比如配药（CHRONIC_DISEASE）
X-Gp-Arc-Code     Arc_code 前端自己获取
```
-----
### 小工具
```text
1 低代码查询方式 入参print_sql=1 打印原生sql 如下图 截图1
```
##### 截图1
![img.png](img.png)
-----

### store/templates.json 模板文件以下内容，案例文件在README.md，同级目录下 ⚠️json 内容根据需求自定义

### 最后完成初始化 如手动调用   
```api
post  /innerapi/v2/init/org-disease 
header 必须入参
    X-Gp-Org-Id 机构ID 前端自己申请写死
    X-Gp-System-Code  系统编码 研发PM定义 
    X-Gp-Disease-Code 疾病编码 业务中台后端开发定义 比如 (SANYA)
    X-Gp-Scene-Code   场景编码 后端开发定义 比如 “慢病配药”（CHRONIC_DISEASE）
    X-Gp-Arc-Code     Arc_code 前端自动获取
json 入参
table_name: 场景表名 向业务总台后端要表名
```
#### 内置api ####
所有api 默认入参
    header 必须入参
    X-Gp-Org-Id 机构ID 前端自己申请写死
    X-Gp-System-Code  系统编码 研发PM定义
    X-Gp-Disease-Code 疾病编码 业务中台后端开发定义 比如 (SANYA)
    X-Gp-Scene-Code   场景编码 后端开发定义 比如 “慢病配药”（CHRONIC_DISEASE）
    X-Gp-Arc-Code     Arc_code 前端自动获取
```text
        地区列表：/v1/v2/region-list 
            入参数 use_permission 使用权限 返回有权限的数据
            不入参数 use_permission 查看所有数据
```