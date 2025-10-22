<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Entities\Business\Resident;

use BrightLiu\LowCode\Entities\Entity;

/**
 * 业务中台居民:基本信息
 *
 * @property string user_id 主索引
 * @property string id_crd_no 身份证号
 * @property string rsdnt_nm 居民姓名
 * @property string slf_tel_no 居民手机号
 * @property string bth_dt 出生日期
 * @property int gdr_cd 性别
 */
class ResidentBasicInfoEntity extends Entity
{
}
