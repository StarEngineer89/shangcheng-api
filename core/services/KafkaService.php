<?php
namespace core\services;

use RdKafka\Producer;
use RdKafka\KafkaConsumer;
use RdKafka\Conf;

class KafkaService
{
    private $producer;
    private $brokers;
    private $consumers = [];

    public function __construct($brokers = '127.0.0.1:9092')
    {
        $this->brokers = $brokers;
    }

    /**
     * 初始化生产者
     */
    public function initProducer()
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('batch.num.messages', 10000); // 每批最多 10000 条消息
        $conf->set('linger.ms', 50); // 50ms 内积累足够多的消息再发送
        $conf->set('queue.buffering.max.messages', 100000); // 生产者队列最多缓存 10 万条消息
        $conf->set('queue.buffering.max.kbytes', 1048576); // 缓冲区最大 1GB
        $conf->set('request.timeout.ms', '30000'); // 增加请求超时时间
        $conf->set('acks', '1');
        $this->producer = new Producer($conf);
        $this->producer->addBrokers($this->brokers);
    }

    /**
     * 创建消费者并订阅主题
     * @param string $topic
     * @param string $groupId
     */
    public function createConsumer(string $topic, string $groupId)
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('group.id', $groupId);
        $conf->set('group.instance.id', 'consumer_'.$groupId);
        $conf->set('fetch.min.bytes', '1'); // 立即拉取消息
        $conf->set('fetch.wait.max.ms', '10'); // 最长等待 10ms
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('session.timeout.ms', '120000'); // 10s 检测掉线，更快 Rebalancing
        $conf->set('heartbeat.interval.ms', '30000'); // 3s 心跳，防止 Kafka 误判掉线
        $conf->set('max.poll.interval.ms', '900000');
        $conf->set('reconnect.backoff.ms', '500'); // 0.5 秒后重试
        $conf->set('reconnect.backoff.max.ms', '5000'); // 最大重试间隔 5 秒
        $conf->set('socket.keepalive.enable',true);	

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        $this->consumers[$topic] = $consumer;
    }

    /**
     * 推送消息到 Kafka
     * @param string $topic
     * @param string $message
     */
    public function produce(string $topic, string $message)
    {
        $topicProducer = $this->producer->newTopic($topic);
        $topicProducer->produce(RD_KAFKA_PARTITION_UA, 0, $message);
        $this->producer->flush(1000);
    }

    /**
     * 批量推送消息到 Kafka
     * @param string $topic
     * @param string $messages
     */
    public function batchProduce(string $topic, array $messages)
    {
        $topicProducer = $this->producer->newTopic($topic);
        foreach ($messages as $message){
            $topicProducer->produce(RD_KAFKA_PARTITION_UA, 0, is_array($message)?json_encode($message):$message);
        }
        $this->producer->poll(0);
        while ($this->producer->getOutQLen() > 0) {
            $this->producer->poll(100);
        }
    }

    /**
     * 消费 Kafka 消息（多主题）
     * @param string $topic
     * @param callable $callback
     */
    public function consume(string $topic, callable $callback)
    {
        if (!isset($this->consumers[$topic])) {
            throw new \Exception("未找到主题：{$topic} 的消费者");
        }
        $consumer = $this->consumers[$topic];
        while (CacheService::get('swoole_status')!=2) {
            $messages = [];
            for ($i = 0; $i < 500; $i++) {
                $message = $consumer->consume(1000);
                if($message->err){
                    break;
                }
                $data = json_decode($message->payload, true);
                if($data){
                    $messages[] = $data;
                }
            }
            if(!empty($messages)){
                $callback($messages);
                $consumer->commitAsync();
            }
        }
    }


    public function commitOffset($topic, $message)
    {
        if (!isset($this->consumers[$topic])) {
            throw new \Exception("未找到主题：{$topic} 的消费者");
        }
        $this->consumers[$topic]->commit($message);
    }



//首先要根据需求创建主题
//./kafka-topics.sh --create \
//--bootstrap-server 127.0.0.1:9092 \
//--replication-factor 1 \
//--partitions 1 \
//--topic Moneylog


// 使用示例
//$kafka = new KafkaClient();
//$kafka->createConsumer('chat_message', 'chat_group');
//$kafka->createConsumer('system_notice', 'notice_group');
//$kafka->produce('chat_message', 'Hello Chat');
//$kafka->produce('system_notice', 'New Notice');

//$kafka->consume('chat_message', function($msg) {
//    echo "[聊天] $msg\n";
//});

//$kafka->consume('system_notice', function($msg) {
//    echo "[通知] $msg\n";
//});


}