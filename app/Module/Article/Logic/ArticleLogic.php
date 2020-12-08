<?php

namespace App\Module\Article\Logic;

use App\Module\Article\Constant\ArticleConstant;
use HyperfPlus\Util\Util;
use Hyperf\Di\Annotation\Inject;
use App\Module\Article\Service\ArticleService;

class ArticleLogic
{
    /**
     * @Inject()
     * @var ArticleService
     */
    private $service;

    /**
     * 创建
     *
     * @param $requestData
     * @return int
     */
    public function create($requestData)
    {
        return $this->service->create($requestData);
    }

    /**
     * 更新
     *
     * @param $requestData
     * @return int
     */
    public function update($requestData)
    {
        $id = $requestData['id'];
        unset($requestData['id']);
        return $this->service->update(['id' => $id], $requestData);
    }

    /**
     * 更新字段
     *
     * @param $requestData
     * @return int
     */
    public function updateField($requestData)
    {
        $id = $requestData['id'];
        unset($requestData['id']);
        return $this->service->update(['id' => $id], $requestData);
    }

    /**
     * 查找
     *
     * @param $requestData
     * @param $p
     * @param $size
     * @return array
     */
    public function search($requestData, $p, $size)
    {
        $list  = $this->service->search($requestData, $p, $size);
        $total = $this->service->count($requestData);
        return Util::formatSearchRes($p, $size, $total, $list);
    }

    /**
     * 获取一行
     *
     * @param $requestData
     * @return array
     */
    public function find($requestData)
    {
        return $this->service->getLineByWhere($requestData);
    }

    /**
     * 获取需要同步到 ElasticSearch 中的文章数据
     *
     * @param int $lastId
     * @param int $count
     * @return array
     */
    public function getSyncToEsArticleData($lastId = 0, $count = 100)
    {
        return $this->service->search([
            'status'    => ArticleConstant::ARTICLE_STATUS_NORMAL,
            'id'        => ['>', $lastId]
        ], 0, $count);
    }
}