<?php

namespace RabbitMq;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQQueue extends Queue implements QueueContract
{
    protected string $exchangeName;
    protected array $config;
    protected AMQPStreamConnection $connection;
    protected AMQPChannel $channel;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->exchangeName = $config['exchange'] ?? 'default';
        $this->connect();
    }

    protected function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['password'],
                $this->config['vhost']
            );
            $this->channel = $this->connection->channel();
            $this->channel->basic_qos(
                $this->config['qos_prefetch_size'] ?? 0,
                $this->config['qos_prefetch_count'] ?? 1,
                $this->config['qos_global'] ?? false
            );
        } catch (\Exception $e) {
            throw new \Exception("Error connecting to RabbitMQ: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public function size($queue = null): int
    {
        $queueInfo = $this->channel->queue_declare($queue ?? $this->config['queue'], true, false, false, false);
        return $queueInfo[1]; // Message count
    }

    public function push($job, $data = '', $queue = null): mixed
    {
        $payload = $this->createPayload($job, $queue);
        $routingKey = $queue ?? $this->config['routing_key'] ?? '';
        $msg = new \PhpAmqpLib\Message\AMQPMessage(
            $payload,
            ['delivery_mode' => $this->config['persistent'] ?? 2] // 2 para persistente
        );

        $this->channel->basic_publish($msg, $this->exchangeName, $routingKey);

        return null;
    }

    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $routingKey = $queue ?? $this->config['routing_key'] ?? '';
        $msg = new \PhpAmqpLib\Message\AMQPMessage(
            $payload,
            ['delivery_mode' => $this->config['persistent'] ?? 2]
        );
        $this->channel->basic_publish($msg, $this->exchangeName, $routingKey);

        return null;
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        // RabbitMQ no tiene un concepto de "later" inherente.
        // Podrías implementarlo publicando con un header y un consumidor que espere.
        // Para este ejemplo, simplemente lo publicamos inmediatamente.
        return $this->push($job, $data, $queue);
    }

    public function pop($queue = null): ?JobContract
    {
        $queueName = $queue ?? $this->config['queue'];
        $envelope = $this->channel->basic_get($queueName, false);

        if ($envelope) {
            $this->channel->basic_ack($envelope->getDeliveryTag());
            return new RabbitMQJob($this->container, $this->channel, $envelope, $queueName);
        }

        return null;
    }
}
