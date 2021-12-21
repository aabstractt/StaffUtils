<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\thread\Thread;
use Predis\Client;
use RioRizkyRainey\PubsubRedis\RedisSubscribeAdapter;

class PubSubThread extends Thread {

    /**
     * @param string $bootsrap
     */
    public function __construct(
        private string $bootsrap
    ) {}

    /**
     * Runs code on the thread.
     */
    protected function onRun(): void {
        $this->composerAutoloaderPath = $this->bootsrap;

        $this->registerClassLoaders();

        $client = new Client('tcp://104.129.48.90:19037?read_write_timeout=-1');

        $client->auth('thatsmypassword');

        $client->connect();

        $adapter = (new RedisSubscribeAdapter())->setClient($client)->setChannel('HELLO');

        $adapter->subscribe(function ($payload): void {
            var_dump($payload);
        });

        /*$loop = $client->pubSubLoop();

        $loop->subscribe('HELLO');

        $loop->rewind();

        $key = $loop->key();
        $value = $loop->current();
        echo $key . PHP_EOL;
        var_dump($value);

        $loop->next();*/
    }
}