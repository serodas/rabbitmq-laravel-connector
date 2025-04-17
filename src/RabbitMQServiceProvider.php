<?php

namespace RabbitMq;

use App\Queue\Connectors\RabbitMQConnector;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('rabbitmq.channel', function ($app) {
            $config = $app['config']->get('queue.connections.rabbitmq');

            $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost']
            );

            return $connection->channel();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /** @var QueueManager $queue */
        $queue = $this->app['queue'];

        $queue->addConnector('rabbitmq', function () {
            return new RabbitMQConnector($this->app);
        });

        if ($this->app->bound('rabbitmq.channel')) {
            \Log::info('El servicio rabbitmq.channel está registrado.');
        } else {
            \Log::error('El servicio rabbitmq.channel no está registrado.');
        }

        $this->ensureExchangeAndQueuesExist();
    }

    protected function ensureExchangeAndQueuesExist(): void
    {
        try {
            \Log::info('Starting ensureExchangeAndQueuesExist');

            /** @var AMQPChannel $channel */
            $channel = $this->app->make('rabbitmq.channel');
            $config = $this->app['config']->get('queue.connections.rabbitmq');

            \Log::info('RabbitMQ configuration: ', $config);

            // Configuración del Exchange
            $exchangeName = $config['exchange'] ?? 'laravel-microservices';
            $exchangeType = $config['exchange_type'] ?? 'direct';

            $channel->exchange_declare(
                $exchangeName,
                $exchangeType,
                false,
                true,
                false
            );

            \Log::info("Exchange '{$exchangeName}' has been declared.");

            // Configuración de las Colas
            $queuesToEnsure = $config['queues'] ?? [];
            foreach ($queuesToEnsure as $queueName => $queueConfig) {
                $channel->queue_declare(
                    $queueName,
                    false,
                    $queueConfig['durable'] ?? true,
                    $queueConfig['exclusive'] ?? false,
                    $queueConfig['auto_delete'] ?? false
                );

                $channel->queue_bind(
                    $queueName,
                    $exchangeName,
                    $queueConfig['routing_key'] ?? ''
                );

                \Log::info("Queue '{$queueName}' declared and linked to'{$exchangeName}'.");
            }
        } catch (\Exception $e) {
            \Log::error('Error in ensureExchangeAndQueuesExist: ' . $e->getMessage());
        }
    }
}
