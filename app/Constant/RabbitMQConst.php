<?php

namespace App\Constant;

/**
 * RabbitMQ 常量类
 *
 * 命名规范：
 * 交换机 Exchange：e.service_name，比如 e.content_service
 * 路由 RoutingKey：k.生产服务.生成场景，比如 k.content_service.article_sync
 * 队列 Queue：q.消费服务.生产服务.生成场景，比如 q.content_service.content_service.article_sync
 *
 * @author duiying <wangyaxiandev@gmail.com>
 * @package App\Constant
 */
class RabbitMQConst
{
    // 交换机
    const EXCHANGE_CONTENT              = 'e.content_service';

    // 路由
    const ROUTING_KEY_ARTICLE_SYNC      = 'k.content_service.article_sync';

    // 队列
    const QUEUE_ARTICLE_SYNC            = 'q.content_service.content_service.article_sync';
}