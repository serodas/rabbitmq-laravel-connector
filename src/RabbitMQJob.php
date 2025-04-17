<?php

namespace RabbitMq;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Envelope;

class RabbitMQJob extends Job implements JobContract
{
    /**
     * The RabbitMQ channel instance.
     *
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;

    /**
     * The RabbitMQ envelope instance.
     *
     * @var \PhpAmqpLib\Envelope
     */
    protected $envelope;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \PhpAmqpLib\Channel\AMQPChannel  $channel
     * @param  \PhpAmqpLib\Envelope  $envelope
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, AMQPChannel $channel, Envelope $envelope, $queue)
    {
        $this->container = $container;
        $this->channel = $channel;
        $this->envelope = $envelope;
        $this->queue = $queue;
    }

    /**
     * Get the raw body string of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->envelope->getBody();
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->released = true;
        $this->channel->basic_ack($this->envelope->getDeliveryTag());
    }

    /**
     * Release the job back onto the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->released = true;
        // Re-publish the message with a delay (requires exchange configuration)
        // A simpler approach is to just nack and requeue
        $this->channel->basic_nack($this->envelope->getDeliveryTag(), false, true);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int|null
     */
    public function attempts()
    {
        $headers = $this->envelope->get('application_headers');
        if (isset($headers['x-death']) && is_array($headers['x-death'])) {
            $count = 0;
            foreach ($headers['x-death'] as $death) {
                if (isset($death['count'])) {
                    $count += $death['count'];
                }
            }
            return $count + 1;
        }
        return 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string|null
     */
    public function getJobId()
    {
        return $this->envelope->getDeliveryTag();
    }

    /**
     * Get the underlying Pheanstalk job.
     *
     * @return \PhpAmqpLib\Envelope
     */
    public function getRawJob()
    {
        return $this->envelope;
    }
}
