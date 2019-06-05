<?php
/**
 * Rabbitmq消息队列应用模块
 */
namespace Lib\Extend\Queue;

use Lib\Logger\Adapter\Logger;
use Phalcon\DI;

class RabbitMq
{
    private $channel        = null;
    private $queue          = null;
    private $message        = null;
    public  $messageCount   = 0;
    public static $rabbitmq = null;

    public function __construct()
    {
        try {
            $rabbitmqConfig = DI::getDefault()->get('config')->rabbitmq->toArray();
            if (empty($rabbitmqConfig)) {
                throw new \Exception('请检查RabbitMq的配置是否正确！');
            }
            self::$rabbitmq = new \AMQPConnection($rabbitmqConfig['rabbitmq1']);   //创建连接
            if (!self::$rabbitmq->connect()) {
                throw new \Exception('RabbitMq1连接失败！');
            }
            $this->channel = new \AMQPChannel(self::$rabbitmq);   //创建信道
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $error = "RabbitMq异常:{$error}" ;
            throw new \Exception($error, 1);
        }
    }

    /**
     * Rabbitmq消息生产端
     * @param array $message 消息内容
     * @param string $exchangeName 交换机名称
     * @param string $queueName 队列名称
     * @param string $routeKey 路由键值
     * @return bool
     * @throws \Exception
     */
    public function rbmaQueueProducer(array $message, string $exchangeName, string $queueName, string $routeKey): bool
    {
        try {
            $message = json_encode($message);
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($exchangeName);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->setFlags(AMQP_DURABLE); //持久化
            $exchange->declareExchange();
            $queue = new \AMQPQueue($this->channel);  //创建队列
            $queue->setName($queueName);  //设置队列名字 如果不存在则添加
            $queue->setFlags(AMQP_DURABLE); //持久化
            $queue->declareQueue();
            $queue->bind($exchangeName, $routeKey);  //绑定
            $result = $exchange->publish($message, $routeKey, AMQP_NOPARAM, array('delivery_mode' => 2));
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq消息写入队列异常:' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Rabbitmq延迟队列消息生产端
     * @param array $message
     * @param string $delayExchangeName
     * @param string $delayQueueName
     * @param string $delayRouteKey
     * @param string $normalExchangeName
     * @param string $normalRouteKey
     * @param int $expiration
     * @return bool
     * @throws \Exception
     */
    public function rbmaDelayQueueProducer(array $message, string $delayExchangeName, string $delayQueueName, string $delayRouteKey, string $normalExchangeName, string $normalRouteKey, int $expiration): bool
    {
        try {
            $message = json_encode($message);
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($delayExchangeName);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->setFlags(AMQP_DURABLE); //持久化
            $exchange->declareExchange();
            $queue = new \AMQPQueue($this->channel);  //创建队列
            $queue->setName($delayQueueName);  //设置队列名字 如果不存在则添加
            $queue->setFlags(AMQP_DURABLE); //持久化
            //设置消息过期后被转到哪个交换机和路由
            $queue->setArguments(array(
                'x-dead-letter-exchange' => $normalExchangeName,
                'x-dead-letter-routing-key' => $normalRouteKey,
            ));
            $queue->declareQueue();
            $queue->bind($delayExchangeName, $delayRouteKey);  //绑定
            $result = $exchange->publish($message, $delayRouteKey, AMQP_NOPARAM, array('delivery_mode' => 2,'expiration' => $expiration));
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq延迟消息写入队列异常:' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Rabbitmq非持久化消息生产端
     * @param array $message 消息内容
     * @param string $exchangeName 交换机名称
     * @param string $queueName 队列名称
     * @param string $routeKey 路由键值
     * @return bool
     * @throws \Exception
     */
    public function rbmaQueueNonDurableProducer(array $message, string $exchangeName, string $queueName, string $routeKey): bool
    {
        try {
            $message = json_encode($message);
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($exchangeName);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->declareExchange();
            $queue = new \AMQPQueue($this->channel);  //创建队列
            $queue->setName($queueName);  //设置队列名字 如果不存在则添加
            $queue->declareQueue();
            $queue->bind($exchangeName, $routeKey);  //绑定
            $result = $exchange->publish($message, $routeKey);
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq消息写入队列异常:' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Rabbitmq消息消费端
     * @param string $exchangeName
     * @param string $queueName
     * @param string $routeKey
     * @return bool
     * @throws \Exception
     */
    public function rbmqQueueConsumer(string $exchangeName, string $queueName, string $routeKey): bool
    {
        try {
            //创建交换机
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($exchangeName);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->setFlags(AMQP_DURABLE); //持久化
            $exchange->declareExchange();
            //创建队列
            $this->queue = new \AMQPQueue($this->channel);
            $this->queue->setName($queueName);
            $this->queue->setFlags(AMQP_DURABLE); //持久化
            $this->messageCount = $this->queue->declareQueue();
            $this->channel->qos(0, 1);
            //绑定交换机与队列，并指定路由键
            $this->queue->bind($exchangeName, $routeKey);
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq消费异常:' . $e->getMessage());
        }
        return true;
    }

    /**
     * Rabbitmq非持久化消息消费端
     * @param string $exchangeName
     * @param string $queueName
     * @param string $routeKey
     * @return bool
     * @throws \Exception
     */
    public function rbmqQueueNonDurableConsumer(string $exchangeName, string $queueName, string $routeKey): bool
    {
        try {
            //创建交换机
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($exchangeName);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->declareExchange();
            //创建队列
            $this->queue = new \AMQPQueue($this->channel);
            $this->queue->setName($queueName);
            $this->messageCount = $this->queue->declareQueue();
            $this->channel->qos(0, 1);
            //绑定交换机与队列，并指定路由键
            $this->queue->bind($exchangeName, $routeKey);
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq消费异常:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 获取消息
     * @param bool $isJson
     * @return array
     * @throws \Exception
     */
    public function getMessage(bool $isJson = true): array
    {
        try {
            $data = [];
            $this->message = $this->queue->get();
            if ($this->message) {
                $data = $this->message->getBody();
                if ($isJson === true) {
                    $data = json_decode($data, true);
                }
            }
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq获取消息异常:' . $e->getMessage());
        }
        return $data;
    }

    /**
     * 消息应答
     * @return bool
     * @throws \Exception
     */
    public function ack(): bool
    {
        try {
            $result = $this->queue->ack($this->message->getDeliveryTag());
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq消息应答异常:' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 消息重新放回队列
     * @return bool
     * @throws \Exception
     */
    public function nack(): bool
    {
        try {
            $result = $this->queue->nack($this->message->getDeliveryTag(), AMQP_REQUEUE);
        } catch (\Throwable $e) {
            throw new \Exception('Rabbitmq重回队列消息应答异常:' . $e->getMessage());
        }

        return $result;
    }
}