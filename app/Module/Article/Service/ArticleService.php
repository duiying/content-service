<?php

namespace App\Module\Article\Service;

use App\Constant\ElasticSearchConst;
use App\Module\Article\Constant\ArticleConstant;
use Hyperf\Di\Annotation\Inject;
use App\Module\Article\Dao\ArticleDao;
use HyperfPlus\Elasticsearch\ElasticSearch;
use HyperfPlus\Log\Log;

class ArticleService
{
    /**
     * @Inject()
     * @var ArticleDao
     */
    private $dao;

    /**
     * @Inject()
     * @var ElasticSearch
     */
    private $es;

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        $this->dao->beginTransaction();
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        $this->dao->rollBack();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->dao->commit();
    }

    /**
     * 创建
     *
     * @param $data
     * @return int
     */
    public function create($data)
    {
        return $this->dao->create($data);
    }

    /**
     * 更新
     *
     * @param array $where
     * @param array $data
     * @return int
     */
    public function update($where = [], $data = [])
    {
        return $this->dao->update($where, $data);
    }

    /**
     * 查找
     *
     * @param array $where
     * @param int $p
     * @param int $size
     * @param string[] $columns
     * @param array $orderBy
     * @return array
     */
    public function search($where = [], $p = 0, $size = 0, $columns = ['*'], $orderBy = [])
    {
        return $this->dao->search($where, $p, $size, $columns, $orderBy);
    }

    /**
     * 获取一行
     *
     * @param array $where
     * @param string[] $columns
     * @param array $orderBy
     * @return array
     */
    public function getLineByWhere($where = [], $columns = ['*'], $orderBy = [])
    {
        return $this->dao->getLineByWhere($where, $columns, $orderBy);
    }

    /**
     * 统计
     *
     * @param array $where
     * @return int
     */
    public function count($where = [])
    {
        return $this->dao->count($where);
    }

    /**
     * 通过 ElasticSearch 搜索
     *
     * @param $params
     * @return array|callable
     */
    public function searchByEs($params)
    {
        return $this->es->esClient->search($params);
    }

    /**
     * 删除 ElasticSearch 中 Article 文档
     *
     * @param $id
     */
    public function deleteEsArticle($id)
    {
        try {
            $this->es->esClient->delete([
                'index'     => ElasticSearchConst::INDEX_ARTICLE,
                'id'        => $id,
            ]);
        } catch (\Exception $exception) {
            Log::error('删除 ES Article 文档失败', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'id' => $id]);
        }
    }

    /**
     * 判断 ElasticSearch 中 Article 文档是否存在
     *
     * @param $id
     * @return bool
     */
    public function existsEsArticle($id)
    {
        return $this->es->esClient->exists([
            'index'     => ElasticSearchConst::INDEX_ARTICLE,
            'id'        => $id,
        ]);
    }

    /**
     * 更新 ElasticSearch 中 Article 文档
     *
     * @param $id
     * @return bool
     */
    public function updateEsArticle($id)
    {
        $article = $this->getLineByWhere(['id' => $id, 'status' => ArticleConstant::ARTICLE_STATUS_NORMAL]);
        if (!$article) return false;

        // 如果文档不存在，首先创建文档
        if (!$this->existsEsArticle($id)) {
            return $this->createEsArticle($id);
        }

        try {
            $this->es->esClient->update([
                'index'     => ElasticSearchConst::INDEX_ARTICLE,
                'id'        => $id,
                'body'      => [
                    'doc' => $article,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('更新 ES Article 文档失败', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'id' => $id]);
            return false;
        }

        return true;
    }

    /**
     * 创建 ElasticSearch 中 Article 文档
     *
     * @param $id
     * @return bool
     */
    public function createEsArticle($id)
    {
        $article = $this->getLineByWhere(['id' => $id, 'status' => ArticleConstant::ARTICLE_STATUS_NORMAL]);
        if (!$article) return false;

        // 如果索引不存在，首先创建索引
        if (!$this->existsEsArticleIndex()) {
            $this->createEsArticleIndex();
        }

        try {
            $this->es->esClient->create([
                'index'     => ElasticSearchConst::INDEX_ARTICLE,
                'id'        => $id,
                'body'      => $article
            ]);
        } catch (\Exception $exception) {
            Log::error('创建 ES Article 文档失败', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'id' => $id]);
            return false;
        }

        return true;
    }

    /**
     * 创建 ElasticSearch 中 Article 索引
     */
    public function createEsArticleIndex()
    {
        $this->es->esClient->indices()->create([
            'index' => ElasticSearchConst::INDEX_ARTICLE,
            'body'  => [
                'settings' => ElasticSearchConst::INDEX_ARTICLE_SETTINGS,
                'mappings' => [
                    'properties' => ElasticSearchConst::INDEX_ARTICLE_MAPPINGS
                ],
            ],
        ]);
    }

    /**
     * 判断 ElasticSearch 中 Article 索引是否存在
     *
     * @return bool
     */
    public function existsEsArticleIndex()
    {
        return $this->es->esClient->indices()->exists(['index' => ElasticSearchConst::INDEX_ARTICLE]);
    }

    /**
     * 删除 ElasticSearch 中 Article 索引
     *
     * @return array
     */
    public function deleteEsArticleIndex()
    {
        return $this->es->esClient->indices()->delete(['index' => ElasticSearchConst::INDEX_ARTICLE]);
    }
}