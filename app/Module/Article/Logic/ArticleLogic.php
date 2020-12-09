<?php

namespace App\Module\Article\Logic;

use App\Constant\ElasticSearchConst;
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
        // 通过 ElasticSearch 搜索
        return $this->searchByEs($requestData, $p, $size);
    }

    /**
     * 通过 ElasticSearch 搜索
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
                'sort' => ['sort' => 'asc', 'ctime' => 'desc']
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