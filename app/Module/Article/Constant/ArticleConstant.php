<?php

namespace App\Module\Article\Constant;

class ArticleConstant
{
    /**
     * 状态
     */
    const ARTICLE_STATUS_DELETE = -1;               // 删除
    const ARTICLE_STATUS_NORMAL = 1;                // 正常

    /**
     * 允许的状态
     */
    const ALLOWED_ARTICLE_STATUS_LIST = [
        self::ARTICLE_STATUS_DELETE,
        self::ARTICLE_STATUS_NORMAL,
    ];
}