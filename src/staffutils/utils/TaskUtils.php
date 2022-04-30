<?php

declare(strict_types=1);

namespace staffutils\utils;

use Closure;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use staffutils\StaffUtils;
use staffutils\task\QueryAsyncTask;

class TaskUtils {

    /** @var string */
    protected static string $host;
    /** @var string */
    protected static string $username;
    /** @var string */
    protected static string $password;
    /** @var string */
    protected static string $dbname;
    /** @var int */
    protected static int $port;

    /**
     * @phpstan-var array<string, Closure(QueryAsyncTask): void>
     */
    private static array $callbacks = [];

    public static function init(): void {
        /** @var array $data */
        $data = StaffUtils::getInstance()->getConfig()->get('mysql');

        $hostSplit = explode(':', $data['host']);

        self::$host = $hostSplit[0];

        self::$username = $data['username'];

        self::$password = $data['password'];

        self::$dbname = $data['dbname'];

        self::$port = (int)($hostSplit[1] ?? 3306);
    }

    /**
     * @param QueryAsyncTask $query
     * @param null|Closure  $callback
     *
     * @phpstan-param Closure(QueryAsyncTask):void|null $callback
     */
    public static function runAsync(QueryAsyncTask $query, Closure $callback = null): void {
        if ($callback !== null) {
            self::$callbacks[spl_object_hash($query)] = $callback;
        }

        $query->host = self::$host;
        $query->user = self::$username;
        $query->password = self::$password;
        $query->database = self::$dbname;
        $query->port = self::$port;

        Server::getInstance()->getAsyncPool()->submitTask($query);
    }

    /**
     * @param QueryAsyncTask $query
     */
    public static function submitAsync(QueryAsyncTask $query): void {
        $callable = self::$callbacks[spl_object_hash($query)] ?? null;

        if (!is_callable($callable)) {
            return;
        }

        $callable($query);
    }

    /**
     * @param Task $task
     * @param int  $delay
     */
    public static function runLater(Task $task, int $delay = 20): void {
        StaffUtils::getInstance()->getScheduler()->scheduleDelayedTask($task, $delay);
    }
}