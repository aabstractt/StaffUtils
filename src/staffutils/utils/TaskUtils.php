<?php

declare(strict_types=1);

namespace staffutils\utils;

use pocketmine\plugin\PluginException;
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
     * @var callable[]
     * @template T of QueryAsyncTask
     * @phpstan-var array<string, callable<T>>
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

        /*if (is_bool(($contents = file_get_contents(StaffUtils::getInstance()->getDataFolder() . 'mysql.sql')))) {
            throw new PluginException('Failed to load contents');
        }

        $contents0 = '';

        foreach (explode("\n" , $contents) as $content) {
            $content = trim($content);

            if ($content === "" || $content === "\n") {
                continue;
            }

            $contents0 .= $content . "\n";
        }

        //TaskHandlerStorage::execute(ExecuteQueriesAsync::class, [$contents0]);*/
    }

    /**
     * @param QueryAsyncTask $query
     * @param callable|null  $callback
     *
     * @template T of QueryAsyncTask
     * @phpstan-param callable(T) : void $callback
     */
    public static function runAsync(QueryAsyncTask $query, ?callable $callback = null): void {
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
}