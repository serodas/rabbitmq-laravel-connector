# RabbitMQ Laravel Connector PHP 8.3

This package provides a seamless integration of RabbitMQ as a queue driver for your Laravel applications. It allows you to leverage the robust messaging capabilities of RabbitMQ within your familiar Laravel environment.

## Installation

Follow these steps to install and configure the RabbitMQ Laravel Connector:

1.  **Require the package via Composer:**

    Open your `composer.json` file and add the following to the `require` section:

    ```json
    "serodas/rabbitmq-laravel-connector": "dev-main"
    ```

    Also, add the following to the `repositories` section of your `composer.json` file:

    ```json
    "repositories": [
        {
            "type": "vcs",
            "url": "[https://github.com/serodas/rabbitmq-laravel-connector.git](https://github.com/serodas/rabbitmq-laravel-connector.git)"
        }
    ],
    ```

    Next, update your Composer dependencies:

    ```bash
    composer update
    ```

2.  **Configure Autoloading:**

    In your `composer.json` file, within the `autoload` section, ensure the following is included under `psr-4`:

    ```json
    "RabbitMq\\": "vendor/serodas/rabbitmq-laravel-connector/src/"
    ```

    If you've made changes, run the following command to update the autoloader:

    ```bash
    composer dump-autoload
    ```

3.  **Configure Queue Connection:**

    Open your `config/queue.php` file and add a new connection named `rabbitmq` with the following configuration:

    ```php
    'rabbitmq' => [
        'driver' => 'rabbitmq',
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'mi_exchange'),
        'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
        'exchange_passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
        'exchange_durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
        'exchange_auto_delete' => env('RABBITMQ_EXCHANGE_AUTO_DELETE', false),
        'queue' => env('RABBITMQ_QUEUE', 'default'), // Default queue name
        'routing_key' => env('RABBITMQ_ROUTING_KEY', ''),
        'persistent' => true,
        'qos_prefetch_size' => 0,
        'qos_prefetch_count' => 1,
        'qos_global' => false,
        'queues' => [
            'emails_topic' => [
                'durable' => true,
                'exclusive' => false,
                'auto_delete' => false,
                'routing_key' => 'emails_topic',
            ],
            'ambassador_topic' => [
                'durable' => true,
                'exclusive' => false,
                'auto_delete' => false,
                'routing_key' => 'ambassador_topic',
            ],
        ],
    ],
    ```

    Also, ensure that the `default` queue connection is set to `rabbitmq`:

    ```php
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),
    ```

4.  **Register the Service Provider:**

    Open your `bootstrap/providers.php` file and add the following line to the `$providers` array:

    ```php
    RabbitMq\RabbitMQServiceProvider::class,
    ```

5.  **Configure Environment Variables:**

    Update your `.env` file with the following RabbitMQ connection details. Adjust these values based on your RabbitMQ server configuration:

    ```env
    QUEUE_CONNECTION=rabbitmq
    RABBITMQ_HOST=shared_rabbitmq
    RABBITMQ_PORT=5672
    RABBITMQ_USER=guest
    RABBITMQ_PASSWORD=guest
    RABBITMQ_VHOST=/

    RABBITMQ_EXCHANGE=laravel_microservices
    RABBITMQ_EXCHANGE_TYPE=direct
    RABBITMQ_EXCHANGE_PASSIVE=false
    RABBITMQ_EXCHANGE_DURABLE=true
    RABBITMQ_EXCHANGE_AUTO_DELETE=false

    RABBITMQ_QUEUE=emails_topic
    RABBITMQ_ROUTING_KEY=emails_topic

    RABBITMQ_PERSISTENT=true
    RABBITMQ_QOS_PREFETCH_SIZE=0
    RABBITMQ_QOS_PREFETCH_COUNT=1
    RABBITMQ_QOS_GLOBAL=false
    ```