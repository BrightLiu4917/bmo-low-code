<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Foundation;

enum Requestable: string
{
    /* --------------------------------------------------------------- */

    // 用户中心-根据TOKEN获取用户详情
    case BMO_AUTH_USER_DETAILS = 'api/apiUser/apiUserDetails';

    // 用户中心-用户在几构下的角色集
    case BMO_AUTH_ORG_USER_ROLES = 'api/org/user/details/roles';

    // 用户中心-获取用户数据权限配置
    case BMO_AUTH_USER_DATA_PMS = 'api/apiDataPms/UserDataPms';

    // 用户中心-获取机构详情
    case BMO_AUTH_ORG_DETAILS = 'innerapi/org/details';

    // 用户中心-获取角色路由权限
    case BMO_AUTH_ROUTER_BY_ROLE = 'api/v2/open/inside/role/frontend-permission';

    // 用户中心-创建角色标签
    case BMO_AUTH_CREATE_ROLE_TAGS = 'innerapi/role/tags/create';

    /* --------------------------------------------------------------- */

    // 问卷中心-创建问卷
    case BMO_QUESTIONNAIRE_CREATE = 'innerapi/wenjuan/create';

    // 问卷中心-删除问卷
    case BMO_QUESTIONNAIRE_DELETE = 'innerapi/wenjuan/delete';

    // 问卷中心-更新问卷
    case BMO_QUESTIONNAIRE_UPDATE = 'innerapi/wenjuan/save';

    // 问卷中心-问卷详情
    case BMO_QUESTIONNAIRE_DETAILS = 'innerapi/wenjuan/show';

    // 问卷中心-获取预览地址
    case BMO_QUESTIONNAIRE_PREVIEW_URL = 'innerapi/wenjuan/preview-url';

    // 问卷中心-获取编辑地址
    case BMO_QUESTIONNAIRE_EDITOR_URL = 'innerapi/wenjuan/editor-url';

    // 问卷中心-获取结果地址(移动端)
    case BMO_QUESTIONNAIRE_MOBILE_RESULT_URL = 'innerapi/answer/mResult-url';

    // 问卷中心-获取结果地址(PC端)
    case BMO_QUESTIONNAIRE_PC_RESULT_URL = 'innerapi/answer/result-url';

    // 问卷中心-导出问卷
    case BMO_QUESTIONNAIRE_EXPORT = 'innerapi/wejuan/export';

    // 问卷中心-获取问卷填报地址(移动端)
    case BMO_QUESTIONNAIRE_MOBILE_ANSWER_URL = 'innerapi/answer/url';

    // 问卷中心-获取问卷填报数据
    case BMO_QUESTIONNAIRE_ANSWER_SHOW = 'innerapi/answer/show';

    /* --------------------------------------------------------------- */

    /* --------------------------------------------------------------- */

    // 消息中心-发送消息
    case BMO_NOTICE_SEND = 'innerapi/v1/message/send';

    /* --------------------------------------------------------------- */
}
