<?php

namespace App\Module\Article\Logic;

use App\Constant\AppErrorCode;
use App\Constant\ElasticSearchConst;
use App\Module\Article\Constant\ArticleConstant;
use HyperfPlus\Exception\AppException;
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

    private $sort = ['sort' => 'asc', 'ctime' => 'desc'];

    /**
     * 检查 status 字段
     *
     * @param $status
     */
    public function checkStatus($status)
    {
        if (!in_array($status, ArticleConstant::ALLOWED_ARTICLE_STATUS_LIST)) {
            throw new AppException(AppErrorCode::REQUEST_PARAMS_INVALID, 'status 参数错误！');
        }
    }

    /**
     * 创建
     *
     * @param $requestData
     * @return int
     */
    public function create($requestData)
    {
        $id = $this->service->create($requestData);

        // 向 RabbitMQ 投递消息
        $this->service->produceToRabbitMQ($id, ArticleConstant::ACTION_TYPE_CREATE);

        return $id;
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

        $updateRes = $this->service->update(['id' => $id], $requestData);

        // 向 RabbitMQ 投递消息
        $this->service->produceToRabbitMQ($id, ArticleConstant::ACTION_TYPE_UPDATE);

        return $updateRes;
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

        // 检查 status 字段
        if (isset($requestData['status'])) $this->checkStatus($requestData['status']);

        if ($requestData['status'] == ArticleConstant::ARTICLE_STATUS_DELETE) {
            // 向 RabbitMQ 投递消息
            $this->service->produceToRabbitMQ($id, ArticleConstant::ACTION_TYPE_DELETE);
        }

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
        /**
         * 为什么不能直接从 ElasticSearch 中进行查询呢？
         * 因为现在的流程是通过 RabbitMQ 异步将 MySQL 中的文章数据写进 ElasticSearch，如果直接读 ElasticSearch，当创建完文章以后，这篇刚创建完的文章在文章列表暂时展示不出来，会有短暂的延迟
         * 因此，无查询条件的时候走查 MySQL 逻辑，存在查询条件的时候走 ElasticSearch 逻辑，这样「大概率」保证了刚创建完的文章，回到文章列表的时候，能够将刚创建的文章展示出来
         * 为什么是「大概率」呢？
         * 如果是主从复制的模式下，先写后查，写主查从，会存在主从延迟的情况
         */

        // 通过 MySQL 查询
        if (!isset($requestData['keywords']) || empty($requestData['keywords'])) {
            $list   = $this->service->search($requestData, $p, $size, ['*'], $this->sort);
            $total  = $this->service->count($requestData);
            foreach ($list as $k => $v) {
                $list[$k]['highlight_title']    = '';
                $list[$k]['highlight_content']  = '';
            }
            return Util::formatSearchRes($p, $size, $total, $list);
        }

        // 通过 ElasticSearch 查询
        return $this->searchByEs($requestData, $p, $size);
    }

    /**
     * 通过 ElasticSearch 查询
     *
     * @param $requestData
     * @param $p
     * @param $size
     * @return array
     */
    public function searchByEs($requestData, $p, $size)
    {
        $params = [
            'index' => ElasticSearchConst::INDEX_ARTICLE,
            'body'  => [
                'query' => [
                    'bool' => [
                        'filter'    => [],
                        'must'      => [],
                    ]
                ],
                'highlight' => [
                    'require_field_match'   => false,
                    'fields'                => ['title' => new \stdClass(), 'content' => new \stdClass()],
                    'pre_tags'              => ["<code>"],
                    'post_tags'             => ["</code>"],
                ],
                'sort' => $this->sort
            ],
            'from' => ($p - 1) * $size,
            'size' => $size,
        ];

        if (isset($requestData['keywords']) && !empty($requestData['keywords'])) {
            $keywordList = array_filter(explode(' ', $requestData['keywords']));

            foreach ($keywordList as $k => $v) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query'     => $v,
                        'fields'    => ['title', 'content'],
                    ]
                ];
            }
        }

        if (isset($requestData['status'])) {
            $params['body']['query']['bool']['filter'][] = ['term' => ['status' => $requestData['status']]];
        }

        $elasticSearchRes = $this->service->searchByEs($params);

        $total  = isset($elasticSearchRes['hits']['total']['value']) ? $elasticSearchRes['hits']['total']['value'] : 0;
        $list   = [];

        if (isset($elasticSearchRes['hits']['hits'])) {
            foreach ($elasticSearchRes['hits']['hits'] as $k => $v) {
                $tmpArticle                         = $v['_source'];
                $tmpArticle['title']                = strip_tags($tmpArticle['title']);
                $tmpArticle['content']              = strip_tags($tmpArticle['content']);

                // 高亮逻辑
                $tmpArticle['highlight_content']    = '';
                $tmpArticle['highlight_title']      = '';
                if (isset($v['highlight']['content']) && !empty($v['highlight']['content'])) {
                    $tmpArticle['highlight_content'] = strip_tags($v['highlight']['content'][0], '<code>');
                }
                if (isset($v['highlight']['title']) && !empty($v['highlight']['title'])) {
                    $tmpArticle['highlight_title'] = strip_tags($v['highlight']['title'][0], '<code>');
                }

                $list[] = $tmpArticle;
            }
        }

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