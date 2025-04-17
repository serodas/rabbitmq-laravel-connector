<?php

namespace RabbitMq;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Foundation\Application;


class RabbitMQConnector implements ConnectorInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function connect(array $config): Queue
    {
        return new RabbitMQQueue($config);
    }
}
