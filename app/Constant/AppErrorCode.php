<?php

namespace App\Constant;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class AppErrorCode extends AbstractConstants
{
    /******************** 公共错误码 begin 30001 ~ 30500 ****************************************************************/

    /**
     * @Message("请求参数错误")
     */
    const REQUEST_PARAMS_INVALID = 30001;

    /******************** 公共错误码 end ********************************************************************************/



    /******************** 文章模块错误码 begin 4000001 ~ 4000100 *********************************************************/



    /******************** 文章模块错误码 end *****************************************************************************/
}