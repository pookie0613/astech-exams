<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisQueueService;

class TestRedis extends Command
{
    protected $signature = 'redis:test {action : publish or subscribe} {--message= : Message to publish} {--channel= : Channel name for subscribing}';
    protected $description = 'Test Redis publishing and subscribing functionality';

    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'publish') {
            $this->testPublish();
        } elseif ($action === 'subscribe') {
            $this->testSubscribe();
        } else {
            $this->error('Invalid action. Use "publish" or "subscribe"');
            return 1;
        }

        return 0;
    }

    private function testPublish()
    {
        $message = $this->option('message') ?: 'Hello from exams service!';
        $routingKey = 'test.routing.key';

        $this->info('Testing Redis Publishing...');
        $this->info('Message: ' . $message);
        $this->info('Routing Key: ' . $routingKey);

        $data = [
            'service' => 'exams',
            'action' => 'test_publish',
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        $redisQueueService = new RedisQueueService();

        if ($redisQueueService->isConnected()) {
            $success = $redisQueueService->publish($data, $routingKey);

            if ($success) {
                $this->info('✓ Message published successfully to Redis');
                $this->info('Check Redis logs or use another terminal to subscribe to the channel');
            } else {
                $this->error('✗ Failed to publish message to Redis');
            }
        } else {
            $this->error('✗ Redis service not connected');
        }
    }

    private function testSubscribe()
    {
        $channel = $this->option('channel') ?: 'training_events';

        $this->info('Testing Redis Subscribing...');
        $this->info('Channel: ' . $channel);
        $this->info('Waiting for messages... (Press Ctrl+C to stop)');

        $redisQueueService = new RedisQueueService();

        if ($redisQueueService->isConnected()) {
            $callback = function ($data) {
                $this->info('Received message:');
                $this->info('Routing Key: ' . ($data['routing_key'] ?? 'N/A'));
                $this->info('Message: ' . json_encode($data['message'] ?? 'N/A'));
                $this->info('Timestamp: ' . ($data['timestamp'] ?? 'N/A'));
                $this->info('---');
            };

            $redisQueueService->subscribe($channel, $callback);
        } else {
            $this->error('✗ Redis service not connected');
        }
    }
}
