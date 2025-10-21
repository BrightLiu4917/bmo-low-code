<?php

declare(strict_types = 1);

namespace App\Resources\LowCode\LowCodeList;

use BrightLiu\LowCode\Tools\Mask;
use Illuminate\Http\Request;
use App\Models\LowCode\LowCodeList;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeList
 */
final class QuerySource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return ["ptt_nm"               => $this->ptt_nm ?? '',
                //varchar(255) COMMENT '本人姓名',
                "id_crd_no"            => $this->id_crd_no ?? '',
                "masked_id_crd_no"     => Mask::idcard(
                    ($this->id_crd_no ?? '')
                ), "masked_slf_tel_no" => Mask::phone(
                ($this->slf_tel_no ?? '')
            ),

                //varchar(50) COMMENT '身份证件号码',
                "user_id"              => $this->user_id ?? '',
                //varchar(100) COMMENT '身份证件号码加密',
                "bth_dt"               => $this->bth_dt ?? null,
                //date COMMENT '出生日期',
                "age"                  => $this->age ?? 0,
                //varchar(10) COMMENT '年龄',
                "slf_tel_no"           => $this->slf_tel_no ?? '',
                //varchar(20) COMMENT '本人电话号码',
                "gdr_cd"               => $this->gdr_cd ?? 9,
                "gdr_nm"               => $this->gdr_nm ?? '',
                //varchar(10) COMMENT '性别名称',
                "bld_cd"               => $this->bld_cd ?? '',
                //varchar(10) COMMENT '血型代码',
                "bld_nm"               => $this->bld_nm ?? '',
                //varchar(20) COMMENT '血型名称',
                "fml_chrnc_dses_hst"   => $this->fml_chrnc_dses_hst ?? '',
                //varchar(255) COMMENT '家族史',
                "fml_hst"              => $this->fml_hst ?? '',
                //varchar(255) COMMENT '家族慢病史',
                "smk_hst"              => $this->smk_hst ?? '',
                //varchar(255) COMMENT '吸烟史',
                "past_hst"             => $this->past_hst ?? '',
                //varchar(255) COMMENT '既往史',
                "algn_src"             => $this->algn_src ?? '',
                //varchar(255) COMMENT '过敏源',
                "liv_hbt"              => $this->liv_hbt ?? '',
                //varchar(255) COMMENT '生活习惯',
                "hgt"                  => $this->hgt ?? 0.00,
                //varchar(20) COMMENT '身高（cm）',
                "wgt"                  => $this->wgt ?? 0.00,
                //varchar(20) COMMENT '体重（kg）',
                "bmi"                  => $this->bmi ?? '',
                //varchar(10) COMMENT 'BMI',
                "curr_addr"            => $this->curr_addr ?? '',
                //varchar(255) COMMENT '现住地址',
                "curr_addr_prv_cd"     => $this->curr_addr_prv_cd ?? '',
                //varchar(20) COMMENT '现住址-省（自治区、直辖市）编码',
                "curr_addr_prv_nm"     => $this->curr_addr_prv_nm ?? '',
                //varchar(255) COMMENT '现住址-省（自治区、直辖市）名称',
                "curr_addr_cty_cd"     => $this->curr_addr_cty_cd ?? '',
                //varchar(20) COMMENT '现住址-市（地区、州）编码',
                "curr_addr_cty_nm"     => $this->curr_addr_cty_nm ?? '',
                //varchar(255) COMMENT '现住址-市（地区、州）名称',
                "curr_addr_cnty_cd"    => $this->curr_addr_cnty_cd ?? "",
                //varchar(20) COMMENT '现住址-县（区）编码',
                "curr_addr_cnty_nm"    => $this->curr_addr_cnty_nm ?? '',
                //varchar(255) COMMENT '现住址-县（区）名称',
                "curr_addr_twn_cd"     => $this->curr_addr_twn_cd ?? '',
                //varchar(20) COMMENT '现住址-乡（镇、街道办事处）编码',
                "curr_addr_twn_nm"     => $this->curr_addr_twn_nm ?? '',
                //varchar(255) COMMENT '现住址-乡（镇、街道办事处）名称',
                "curr_addr_vlg_cd"     => $this->curr_addr_vlg_cd ?? "",
                //varchar(20) COMMENT '现住址-村（街、路、弄）编码',
                "curr_addr_vlg_nm"     => $this->curr_addr_vlg_nm ?? "",
                //varchar(255) COMMENT '现住址-村（街、路、弄）名称',
                "pblc_hlth_flg"        => $this->pblc_hlth_flg ?? '',
                //int COMMENT '公卫标识@0否/1是',
                "dth_flg"              => $this->dth_flg ?? "",
                //int COMMENT '死亡标识@0否/1是',
                "dth_flg_nm"           => match (($this->dth_flg ?? -1)) {
                    0       => '否',
                    1       => '是',
                    default => '--',
                },

                "dth_dt"          => $this->dth_dt ?? null,
                //datetime COMMENT '死亡日期',
                "psn_adtn_flg"    => $this->psn_adtn_flg ?? 0,
                //int COMMENT '人员新增标识@0否/1是',
                "psn_adtn_flg_nm" => match (($this->psn_adtn_flg ?? -1)) {
                    0       => '否',
                    1       => '是',
                    default => '--',
                },

                "psn_adtn_tm"         => $this->psn_adtn_tm ?? null,
                //datetime COMMENT '人员新增时间',
                "spcl_crt_rcd_flg"    => $this->spcl_crt_rcd_flg ?? 0,
                //int COMMENT '专项建档标识@0否/1是',
                "spcl_crt_rcd_flg_nm" => match (($this->spcl_crt_rcd_flg ??-1)) {
                    0       => '否',
                    1       => '是',
                    default => '--',
                },

                "rcd_mng_cmnt_cd"       => $this->rcd_mng_cmnt_cd ?? '',
                //varchar(50) COMMENT '档案管理机构所属医共体编码',
                "rcd_mng_cmnt_nm"       => $this->rcd_mng_cmnt_nm ?? '',
                //varchar(255) COMMENT '档案管理机构所属医共体名称',
                "rcd_mng_org_cd"        => $this->rcd_mng_org_cd ?? '',
                //varchar(50) COMMENT '档案管理机构编码',
                "rcd_mng_org_nm"        => $this->rcd_mng_org_nm ?? '',
                //varchar(255) COMMENT '档案管理机构名称',
                "rcd_mng_team_cd"       => $this->rcd_mng_team_cd ?? '',
                //varchar(50) COMMENT '档案管理团队编码',
                "rcd_mng_team_nm"       => $this->rcd_mng_team_nm ?? '',
                //varchar(255) COMMENT '档案管理团队名称',
                "rspsblt_dct_cd"        => $this->rspsblt_dct_cd ?? '',
                //varchar(50) COMMENT '责任医生编码',
                "rspsblt_dct_nm"        => $this->rspsblt_dct_nm ?? '',
                //varchar(255) COMMENT '责任医生名称',
                "rspsblt_dct_id_crd_no" => $this->rspsblt_dct_id_crd_no ?? '',
                //varchar(50) COMMENT '责任医生身份证号码',
                "rspsblt_dct_tel_no"    => $this->rspsblt_dct_tel_no ?? '',
                //varchar(50) COMMENT '责任医生电话号码',
                "sgn_ctrct_stts_cd"     => $this->sgn_ctrct_stts_cd ?? '',
                //int COMMENT '签约状态编码@3正式签约/5到期/7未签约',

                "sgn_ctrct_stts_cd_nm" => match (($this->sgn_ctrct_stts_cd ?? -1)) {
                    3       => '正式签约',
                    5       => '到期',
                    7       => '未签约',
                    default => '--',
                },


                "sgn_ctrct_stts_nm"      => $this->sgn_ctrct_stts_nm ?? '',
                //varchar(50) COMMENT '签约状态名称',
                "sgn_ctrct_dt"           => $this->sgn_ctrct_dt ?? null,
                //datetime COMMENT '签约日期',
                "sgn_ctrct_org_cd"       => $this->sgn_ctrct_org_cd ?? '',
                //varchar(50) COMMENT '签约机构编码',
                "sgn_ctrct_org_nm"       => $this->sgn_ctrct_org_nm ?? '',
                //varchar(255) COMMENT '签约机构名称',
                "sgn_ctrct_dct_cd"       => $this->sgn_ctrct_dct_cd ?? '',
                //varchar(50) COMMENT '签约医生编码（家庭医生）',
                "sgn_ctrct_dct_nm"       => $this->sgn_ctrct_dct_nm ?? '',
                //varchar(255) COMMENT '签约医生姓名（家庭医生）',
                "sgst_sgn_ctrct_team_cd" => $this->sgst_sgn_ctrct_team_cd ?? '',
                //varchar(50) COMMENT '推荐签约团队编码',
                "sgst_sgn_ctrct_team_nm" => $this->sgst_sgn_ctrct_team_nm ?? '',
                //varchar(255) COMMENT '推荐签约团队名称',
                "spcl_dses_dct_nm"       => $this->spcl_dses_dct_nm ?? '',
                //varchar(255) COMMENT '专病医生',
                "cfm_diag_flg"           => $this->cfm_diag_flg ?? -1,
                //int COMMENT '专病确诊标识@0否/1是',
                "cfm_diag_flg_nm"        => match (($this->cfm_diag_flg ?? -1)) {
                    1       => '是',
                    0       => '否',
                    9       => '其他',
                    default => '--',
                },


                "cfm_diag_dt"            => $this->cfm_diag_dt ?? null,
                //datetime COMMENT '确诊时间',
                "spcl_dses_year_vst_tm"  => $this->spcl_dses_year_vst_tm ??
                    null,        //varchar(255) COMMENT '最近一年专病就诊次数',
                "last_spcl_dses_vst_tm"  => $this->last_spcl_dses_vst_tm ??
                    null,        //datetime COMMENT '最近一次专病就诊时间',
                "rmrk"                   => $this->rmrk ?? '',
                //varchar(255) COMMENT '备注',
                "rsk_elmt"               => $this->rsk_elmt ?? '',
                //varchar(255) COMMENT '风险因素',
                "ptt_crwd_clsf_cd"       => $this->ptt_crwd_clsf_cd ?? '',
                //int COMMENT '人群分类代码',
                "ptt_crwd_clsf_nm"       => $this->ptt_crwd_clsf_nm ?? '',
                //varchar(50) COMMENT '人群分类名称',
                "ptt_crwd_clsf_tm"       => $this->ptt_crwd_clsf_tm ?? null,
                //datetime COMMENT '人群分类时间',
                "incprt_into_mng_flg"    => $this->incprt_into_mng_flg ?? -1,
                //int COMMENT '纳管标识@0待纳管/1已纳管/2推荐纳管/9出组',
                "incprt_into_mng_flg_nm" => match (($this->incprt_into_mng_flg ?? -1)) {
                    1       => '已纳管',
                    0       => '待纳管',
                    2       => '推荐纳管',
                    9       => '出组',
                    default => '--',
                },

                "incprt_into_mng_tm" => $this->incprt_into_mng_tm ?? '',
                //datetime COMMENT '纳管时间',
                "out_grp_tm"         => $this->out_grp_tm ?? null,
                //datetime COMMENT '出组时间',
                "out_grp_cas"        => $this->out_grp_cas ?? '',
                //varchar(255) COMMENT '出组原因',
                "infmd_agr_rcd_stts" => $this->infmd_agr_rcd_stts ?? "",
                //int COMMENT '知情同意书签署状态@0未签署/1已签署',

                "infmd_agr_rcd_stts_nm" => match (($this->infmd_agr_rcd_stts ?? -1)) {
                    1       => '已签署',
                    0       => '未签署',
                    9       => '其他',
                    default => '--',
                },


                "incprt_into_mng_org_cd"      => $this->incprt_into_mng_org_cd
                    ?? '',        //varchar(50) COMMENT '纳管机构代码',
                "incprt_into_mng_org_nm"      => $this->incprt_into_mng_org_nm
                    ?? '',        //varchar(255) COMMENT '纳管机构名称',
                "incprt_into_mng_dct_cd"      => $this->incprt_into_mng_dct_cd
                    ?? '',        //varchar(50) COMMENT '纳管医生编码',
                "incprt_into_mng_dct_nm"      => $this->incprt_into_mng_dct_nm
                    ?? '',        //varchar(255) COMMENT '纳管医生姓名',
                "incprt_into_mng_dct_team_cd" => $this->incprt_into_mng_dct_team_cd
                    ?? '', //varchar(50) COMMENT '纳管医生团队编码',
                "incprt_into_mng_dct_team_nm" => $this->incprt_into_mng_dct_team_nm
                    ?? '', //varchar(255) COMMENT '纳管医生团队名称',
                "fst_vst_tm"                  => $this->fst_vst_tm ?? null,
                //datetime COMMENT '初诊时间',
                "sbsqt_vst_tm"                => $this->sbsqt_vst_tm ?? null,
                //datetime COMMENT '复诊时间',
                "next_rvw_tm"                 => $this->next_rvw_tm ?? null,
                //datetime COMMENT '下次复查时间',
                "rvw_flg"                     => $this->rvw_flg ?? 9,
                //int COMMENT '复查标识@0否/1是/9其他',
                "rvw_flg_nm"                  => match (($this->rvw_flg ?? -1)) {
                    1       => '是',
                    0       => '否',
                    9       => '其他',
                    default => '--',
                },

                "on_tm_rvw_flg" => $this->on_tm_rvw_flg ?? 9,
                //int COMMENT '按时复查标识@0否/1是/9未知',

                "on_tm_rvw_flg_nm" => match (($this->on_tm_rvw_flg ?? -1)) {
                    1       => '是',
                    0       => '否',
                    9       => '未知',
                    default => '--',
                },


                "cplct_dses_nms" => $this->cplct_dses_nms ?? '',
                //varchar(255) COMMENT '并发症疾名称（多个疾病以、分割）',
                "upd_dt"         => $this->upd_dt ?? null,
                //datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                "crt_tm"         => $this->crt_tm ?? null,
                //datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                "invld_flg"      => $this->invld_flg ?? 0,

                "invld_flg_nm" => match (($this->invld_flg ?? 0)) {
                    1       => '是',
                    0       => '否',
                    default => '--',
                },

                "incprt_into_mng_src"    => $this->incprt_into_mng_src ?? -1,
                //int COMMENT '纳管来源@1手动纳管/2推荐纳管/3知情同意书签署/4企业微信',
                "incprt_into_mng_src_nm" => match (($this->incprt_into_mng_src ?? -1)) {
                    1       => '手动纳管',
                    2       => '推荐纳管',
                    3       => '知情同意书签署',
                    4       => '企业微信',
                    default => '--',
                },];
    }
}
