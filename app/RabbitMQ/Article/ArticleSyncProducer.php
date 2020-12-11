<?php

declare(strict_types=1);

namespace App\RabbitMQ\Article;

use App\Constant\RabbitMQConst;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

/**
 * ArticleSyncProducer
 *
 * @Producer()
 */
class ArticleSyncProducer extends ProducerMessage
{
    public $exchange    = RabbitMQConst::EXCHANGE_CONTENT;
    public $routingKey  = RabbitMQConst::ROUTING_KEY_ARTICLE_SYNC;
    public $type        = Type::DIRECT;

    public function __construct($data)
    {
        $this->payload = $data;
    }
}