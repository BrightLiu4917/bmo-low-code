<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodeList;

use BrightLiu\LowCode\Tools\Mask;
use Illuminate\Http\Request;
use BrightLiu\LowCode\Models\LowCodeList;
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
        return [
            "id_crd_no"                     => $this->id_crd_no ?? '',
            "id_crd_no.variant"              => Mask::idcard($this->id_crd_no ??
                ''),
            "ctct_tel_no.variant"            => Mask::phone($this->ctct_tel_no ??
                ''),
            "user_id"                       => $this->user_id ?? '',
            "diet_rcd_bsns_rcd_tm"          => $this->diet_rcd_bsns_rcd_tm ??
                null,
            "bmi_arr_biz_rcd_tm"            => $this->bmi_arr_biz_rcd_tm ??
                null,
            "mdc_hst_infmt_bsns_rcd_tm"     => $this->mdc_hst_infmt_bsns_rcd_tm
                ?? null,
            "dtlc_prs_rcd_biz_rcd_tm"       => $this->dtlc_prs_rcd_biz_rcd_tm ??
                null,
            "tc_sgn_rcd_biz_rcd_tm"         => $this->tc_sgn_rcd_biz_rcd_tm ??
                null,
            "rct_vst_tm"                    => $this->rct_vst_tm ?? null,
            "rct_vst_tp"                    => $this->rct_vst_tp ?? null,
            "rvct_dscg_mng_tm"              => $this->rvct_dscg_mng_tm ?? null,
            "weight_arr_biz_rcd_tm"         => $this->weight_arr_biz_rcd_tm ??
                null,
            "next_rvw_tm"                   => $this->next_rvw_tm ?? null,
            "dth_dt"                        => $this->dth_dt ?? null,
            "rct_diag"                      => $this->rct_diag ?? null,
            "rct_disp_drug_tm"              => $this->rct_disp_drug_tm ?? null,
            "rct_task_tm"                   => $this->rct_task_tm ?? null,
            "stlc_prs_rcd_biz_rcd_tm"       => $this->stlc_prs_rcd_biz_rcd_tm ??
                null,
            "fbg_sgn_rcd_biz_rcd_tm"        => $this->fbg_sgn_rcd_biz_rcd_tm ??
                null,
            "height_arr_biz_rcd_tm"         => $this->height_arr_biz_rcd_tm ??
                null,
            "bth_dt"                        => $this->bth_dt ?? null,
            "waist_arr_biz_rcd_tm"          => $this->waist_arr_biz_rcd_tm ??
                null,
            "sgn_ctrct_dt"                  => $this->sgn_ctrct_dt ?? null,
            "gmt_created"                   => $this->gmt_created ?? null,
            "gmt_modified"                  => $this->gmt_modified ?? null,

            // 字符串字段
            "sgn_ctrct_stts_cd"             => $this->sgn_ctrct_stts_cd ?? '',
            "curr_addr"                     => $this->curr_addr ?? '',
            "sgn_dct_tel_no"                => $this->sgn_dct_tel_no ?? '',
            "dscg_mng_tp_nm"                => $this->dscg_mng_tp_nm ?? '',
            "ptt_dscg_tp"                   => $this->ptt_dscg_tp ?? '',
            "slf_tel_no"                    => $this->slf_tel_no ?? '',
            "slf_tel_no.variant"             => Mask::phone($this->slf_tel_no ??
                ''),
            "hrgst_tp_cd"                   => $this->hrgst_tp_cd ?? '',
            "dscg_mng_tp"                   => $this->dscg_mng_tp ?? '',
            "rct_task_id"                   => $this->rct_task_id ?? '',
            "diet_rcd_diet_hbt_cd"          => $this->diet_rcd_diet_hbt_cd ??
                '',
            "drnk_rcd_drnk_frq_cd"          => $this->drnk_rcd_drnk_frq_cd ??
                '',
            "rct_vst_org_id"                => $this->rct_vst_org_id ?? '',
            "rspsblt_dct_org_nm"            => $this->rspsblt_dct_org_nm ?? '',
            "out_hosp_diag"                 => $this->out_hosp_diag ?? '',
            "rspsblt_dct_nm"                => $this->rspsblt_dct_nm ?? '',
            "diet_rcd_eat_hbt_dscpt"        => $this->diet_rcd_eat_hbt_dscpt ??
                '',
            "rct_vst_tp_nm"                 => $this->rct_vst_tp_nm ?? '',
            "sgn_ctrct_org_cd"              => $this->sgn_ctrct_org_cd ?? '',
            "rct_vst_dpt_cd"                => $this->rct_vst_dpt_cd ?? '',
            "curr_addr_cnty_cd"             => $this->curr_addr_cnty_cd ?? '',
            "smk_rcd_smk_cdt_cd"            => $this->smk_rcd_smk_cdt_cd ?? '',
            "rspsblt_dct_cd"                => $this->rspsblt_dct_cd ?? '',
            "curr_addr_prv_cd"              => $this->curr_addr_prv_cd ?? '',
            "curr_addr_cty_cd"              => $this->curr_addr_cty_cd ?? '',
            "alive_cd"                      => $this->alive_cd ?? '',
            "disp_drug_hospi_cd"            => $this->disp_drug_hospi_cd ?? '',
            "curr_addr_twn_cd"              => $this->curr_addr_twn_cd ?? '',
            "rct_vst_dct_cd"                => $this->rct_vst_dct_cd ?? '',
            "sgn_ctrct_stts_nm"             => $this->sgn_ctrct_stts_nm ?? '',
            "curr_addr_vlg_cd"              => $this->curr_addr_vlg_cd ?? '',
            "rh_cd"                         => $this->rh_cd ?? '',
            "curr_addr_twn_nm"              => $this->curr_addr_twn_nm ?? '',
            "rct_disp_hosp_cd"              => $this->rct_disp_hosp_cd ?? '',
            "rct_vst_org_nm"                => $this->rct_vst_org_nm ?? '',
            "sgn_ctrct_dct_nm"              => $this->sgn_ctrct_dct_nm ?? '',
            "rct_vst_dct_nm"                => $this->rct_vst_dct_nm ?? '',
            "rspsblt_dct_org_cd"            => $this->rspsblt_dct_org_cd ?? '',
            "rspsblt_dct_tel_no"            => $this->rspsblt_dct_tel_no ?? '',
            "empi"                          => $this->empi ?? '',
            "rct_vst_no"                    => $this->rct_vst_no ?? '',
            "sgn_famy_doct_tem"             => $this->sgn_famy_doct_tem ?? '',
            "ptt_dscg_tp_nm"                => $this->ptt_dscg_tp_nm ?? '',
            "sgn_ctrct_org_nm"              => $this->sgn_ctrct_org_nm ?? '',
            "drnk_rcd_drnk_flg"             => $this->drnk_rcd_drnk_flg ?? '',
            "hlth_lvl"                      => $this->hlth_lvl ?? '',
            "curr_addr_prv_nm"              => $this->curr_addr_prv_nm ?? '',
            "disp_prescri"                  => $this->disp_prescri ?? '',
            "curr_addr_cty_nm"              => $this->curr_addr_cty_nm ?? '',
            "bmi_dscpt_arr_bmi_dscpt"       => $this->bmi_dscpt_arr_bmi_dscpt ??
                '',
            "abo_cd"                        => $this->abo_cd ?? '',
            "curr_addr_vlg_nm"              => $this->curr_addr_vlg_nm ?? '',
            "gdr_cd.variant"                => ($this->gdr_cd ?? '') == 1 ? "男":'女',
            "ntn_cd"                        => $this->ntn_cd ?? '',
            "ctct_nm"                       => $this->ctct_nm ?? '',
            "rct_vst_dpt_nm"                => $this->rct_vst_dpt_nm ?? '',
            "rct_disp_hosp_nm"              => $this->rct_disp_hosp_nm ?? '',
            "sgn_ctrct_dct_cd"              => $this->sgn_ctrct_dct_cd ?? '',
            "disp_diag"                     => $this->disp_diag ?? '',
            "curr_addr_cnty_nm"             => $this->curr_addr_cnty_nm ?? '',
            "dscg_mng_tm"                   => $this->dscg_mng_tm ?? '',
            "rsdnt_nm"                      => $this->rsdnt_nm ?? '',
            "disp_drug_hospi_nm"            => $this->disp_drug_hospi_nm ?? '',
            "key_populab_tag"               => $this->key_populab_tag ?? '',

            // 数值字段
            "fbg_sgn_rcd_fbg"               => $this->fbg_sgn_rcd_fbg ?? 0,
            "bmi_arr_bmi"                   => $this->bmi_arr_bmi ?? 0,
            "tc_sgn_rcd_tc"                 => $this->tc_sgn_rcd_tc ?? 0,
            "rct_task_status"               => $this->rct_task_status ?? 0,
            "dscg_mng_flg"                  => $this->dscg_mng_flg ?? 0,
            "stlc_prs_rcd_stlc_prs"         => $this->stlc_prs_rcd_stlc_prs ??
                0,
            "dtlc_prs_rcd_dtlc_prs"         => $this->dtlc_prs_rcd_dtlc_prs ??
                0,
            "is_testing"                    => $this->is_testing ?? 0,
            "exrc_rcd_exrc_drt"             => $this->exrc_rcd_exrc_drt ?? 0,
            "rct_test_flag"                 => $this->rct_test_flag ?? 0,
            "rct_exam_flag"                 => $this->rct_exam_flag ?? 0,
            "diet_rcd_vg_in_vlu"            => $this->diet_rcd_vg_in_vlu ?? 0,
            "smk_rcd_smk_drt"               => $this->smk_rcd_smk_drt ?? 0,
            "dth_flg"                       => $this->dth_flg ?? 0,
            "diet_rcd_meat_in_vlu"          => $this->diet_rcd_meat_in_vlu ?? 0,
            "weight_arr_weight"             => $this->weight_arr_weight ?? 0,
            "drnk_rcd_drnk_drt"             => $this->drnk_rcd_drnk_drt ?? 0,
            "age"                           => $this->age ?? 0,
            "waist_arr_waist"               => $this->waist_arr_waist ?? 0,
            "height_arr_height"             => $this->height_arr_height ?? 0,
            "diet_rcd_day_stpl_fd_vlu"      => $this->diet_rcd_day_stpl_fd_vlu
                ?? 0,
            "diet_rcd_frt_in_vlu"           => $this->diet_rcd_frt_in_vlu ?? 0,
            "is_deleted"                    => $this->is_deleted ?? 0,

            // 文本字段
            "exrc_rcd_exrc_way_expln"       => $this->exrc_rcd_exrc_way_expln ??
                '',
            "mdc_hst_infmt_past_hst"        => $this->mdc_hst_infmt_past_hst ??
                '',
            "mdc_hst_infmt_fml_hst"         => $this->mdc_hst_infmt_fml_hst ??
                '',
            "diet_rcd_diet_prfnce_dscpt"    => $this->diet_rcd_diet_prfnce_dscpt
                ?? '',
            "mdc_hst_infmt_algn_hst"        => $this->mdc_hst_infmt_algn_hst ??
                '',
            "mdc_hst_infmt_oprt_hst"        => $this->mdc_hst_infmt_oprt_hst ??
                '',
            "diet_rcd_day_stpl_fd_tp_dscpt" => $this->diet_rcd_day_stpl_fd_tp_dscpt
                ?? '',

            // 状态字段的文本描述
            "dscg_mng_flg.variant"               => match (($this->dscg_mng_flg ?? 0)) {
                0       => '否',
                1       => '是',
                default => '--',
            },
            "dth_flg.variant"                    => match (($this->dth_flg ?? 0)) {
                0       => '否',
                1       => '是',
                default => '--',
            },
            "is_testing.variant"                 => match (($this->is_testing ?? 0)) {
                0       => '否',
                1       => '是',
                default => '--',
            },
            "is_deleted.variant"                 => match (($this->is_deleted ?? 0)) {
                0       => '正常',
                1       => '删除',
                default => '--',
            },


            //业务字段
            "join_time" => $this->join_time ?? null,
            "refuse_time" => $this->refuse_time ?? null,
            "exit_time" => $this->exit_time ?? null,
            "circulation_time" => $this->circulation_time ?? null,

            "site_name" => $this->site_name ?? '',
            "operator_name" => $this->operator_name ?? '',
            "join_org_code" => $this->join_org_code ?? '',

            "crowd_type_nm" => $this->crowd_type_nm ?? '',
            "join_org_name" => $this->join_org_name ?? '',
            "aid_phone" => $this->aid_phone ?? '',
            "masked_aid_phone" => Mask::phone($this->aid_phone ?? ''),
            "aid_contact" => $this->aid_contact ?? '',
            "out_title" => $this->out_title ?? '',
            "out_remark" => $this->out_remark ?? '',
            "circulation_name" => $this->circulation_name ?? '',
            "now_twn_code" => $this->now_twn_code ?? '',
            "now_twn_name" => $this->now_twn_name ?? '',
            "now_vlg_code" => $this->now_vlg_code ?? '',
            "now_vlg_name" => $this->now_vlg_name ?? '',

            "service_center_site_id" => $this->service_center_site_id ?? 0,
            "operator_id" => $this->operator_id ?? 0,
            "service_center_site_vlg_id" => $this->service_center_site_vlg_id ?? 0,
            "circulation_status" => $this->circulation_status ?? -1,
            "invitaiton_status" => $this->invitaiton_status ?? -1,
            '_crowds'=> $this->crowds ?? '',

            "circulation_status.variant" => match (($this->circulation_status ?? -1)) {
                0 => '待流转',
                1 => '已流转',
                2 => '流转拒绝',
                3 => '流转同意',
                default => '--',
            },
            "invitaiton_status.variant" => match (($this->invitaiton_status ?? -1)) {
                0 => '待邀请',
                1 => '已邀请',
                2 => '已拒绝',
                3 => '已同意',
                default => '--',
            },
        ];
    }
}
