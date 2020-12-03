<?php

namespace App\Module\Article\Dao;

use HyperfPlus\MySQL\MySQLDao;

class ArticleDao extends MySQLDao
{
    public $connection = 'content';
    public $table = 't_content_article';
}