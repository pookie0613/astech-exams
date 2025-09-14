<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisQueueService
{
    private $redis;
    private $isInitialized = false;

    public function __construct()
    {
        $this->initializeConnection();
    }

    private function initializeConnection()
    {
        try {
            // Test Redis connection
            $testKey = 'redis_connection_test_' . uniqid();
            Redis::set($testKey, 'test', 'EX', 10);
            $result = Redis::get($testKey);
            Redis::del($testKey);

            if ($result === 'test') {
                $this->isInitialized = true;
                Log::info('Successfully connected to Redis');
            } else {
                throw new \Exception('Redis connection test failed');
            }

        } catch (\Exception $e) {
            Log::error('Redis connection failed: ' . $e->getMessage());
            $this->isInitialized = false;
        }
    }

    public function isConnected(): bool
    {
        try {
            if (!$this->isInitialized) {
                return false;
            }

            $result = Redis::ping();
            return $result === '+PONG' || $result === true;
        } catch (\Exception $e) {
            Log::warning('Error checking Redis connection: ' . $e->getMessage());
            return false;
        }
    }

    public function publish($message, $routingKey)
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $channel = 'training_events';
            $result = Redis::publish($channel, json_encode([
                'routing_key' => $routingKey,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]));

            Log::info('Message published to Redis', ['routing_key' => $routingKey, 'subscribers' => $result]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to publish message to Redis: ' . $e->getMessage());
            return false;
        }
    }

    public function subscribe($channel, $callback)
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $pubsub = Redis::pubsub();
            $pubsub->subscribe($channel);

            foreach ($pubsub as $message) {
                if ($message->kind === 'message') {
                    $data = json_decode($message->payload, true);
                    $callback($data);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to consume messages from Redis: ' . $e->getMessage());
            return false;
        }
    }
}
