<?php

declare(strict_types=1);

namespace App\RabbitMQ\Article;

use App\Constant\RabbitMQConst;
use App\Module\Article\Constant\ArticleConstant;
use App\Module\Article\Service\ArticleService;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Di\Annotation\Inject;
use HyperfPlus\Log\Log;

/**
 * ArticleSyncConsumer
 *
 * @Consumer()
 */
class ArticleSyncConsumer extends ConsumerMessage
{
    public $exchange    = RabbitMQConst::EXCHANGE_CONTENT;
    public $routingKey  = RabbitMQConst::ROUTING_KEY_ARTICLE_SYNC;
    public $queue       = RabbitMQConst::QUEUE_ARTICLE_SYNC;
    public $type        = Type::DIRECT;

    /**
     * @Inject()
     * @var ArticleService
     */
    private $articleService;

    public function consume($data): string
    {
        $actionType     = $data['action_type'];
        $id             = $data['id'];

        if ($actionType === ArticleConstant::ACTION_TYPE_CREATE) {
            try {
                $this->articleService->createEsArticle($id);
            } catch (\Exception $exception) {
                Log::error('创建 ElasticSearch 中 Article 文档异常', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'data' => $data]);
            }
        } elseif ($actionType === ArticleConstant::ACTION_TYPE_UPDATE) {
            try {
                $this->articleService->updateEsArticle($id);
            } catch (\Exception $exception) {
                Log::error('更新 ElasticSearch 中 Article 文档异常', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'data' => $data]);
            }
        } elseif ($actionType === ArticleConstant::ACTION_TYPE_DELETE) {
            try {
                $this->articleService->deleteEsArticle($id);
            } catch (\Exception $exception) {
                Log::error('删除 ElasticSearch 中 Article 文档异常', ['code' => $exception->getCode(), 'msg' => $exception->getMessage(), 'data' => $data]);
            }
        } else {
            Log::error('文章同步消费未知操作', ['data' => $data]);
        }

        return Result::ACK;
    }
}