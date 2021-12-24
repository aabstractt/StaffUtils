<?php

declare(strict_types=1);

namespace staffutils\task;

use Exception;
use LogicException;
use pocketmine\plugin\PluginException;
use ReflectionClass;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use staffutils\BanEntry;
use staffutils\StaffResult;
use staffutils\utils\MySQL;
use staffutils\utils\TaskUtils;

abstract class QueryAsyncTask extends AsyncTask {

    /** @var string */
    public string $host;
    /** @var string */
    public string $user;
    /** @var string */
    public string $password;
    /** @var string */
    public string $database;
    /** @var int */
    public int $port;
    /** @var Exception|null */
    private ?Exception $logException = null;

    private int $index = 0;

    /**
     * @param MySQL $mysqli
     */
    abstract public function query(MySQL $mysqli): void;

    final public function onRun(): void {
        try {
            $this->query($mysqli = new MySQL($this->host, $this->user, $this->password, $this->database, $this->port));

            $mysqli->close();
        } catch (Exception $e) {
            $this->logException = $e;
        }
    }

    public function onCompletion(): void {
        if ($this->logException !== null) {
            Server::getInstance()->getLogger()->critical("Could not execute asynchronous task " . (new ReflectionClass($this))->getShortName() . ": Task crashed");

            Server::getInstance()->getLogger()->logException($this->logException);

            return;
        }

        TaskUtils::submitAsync($this);
    }

    /**
     * @param mixed ...$newResult
     */
    protected function addResult(mixed... $newResult): void {
        if (is_array($result = $this->getResult())) {
            $this->setResult(array_merge($result, [$newResult]));
        }
    }

    /**
     * @return int
     */
    public function asInt(): int {
        if (is_array($result = $this->getResult())) {
            return (int)(array_values($result)[$this->index++] ?? throw new PluginException('Result not found'));
        }

        return is_int($value = $this->getResult()) ? $value : throw new LogicException('Result not is integer');
    }

    /**
     * @return string
     */
    public function resultString(): string {
        if (is_array($result = $this->getResult())) {
            return (string)(array_values($result)[$this->index++] ?? throw new PluginException('Result not found'));
        }

        return is_string($result) ? $result : throw new LogicException('Result not is string');
    }

    /**
     * @return BanEntry|null
     */
    public function entryResult(): ?BanEntry {
        if (is_array($result = $this->getResult())) {
            return array_values($result)[$this->index++] ?? null;
        }

        return $result instanceof BanEntry ? $result : null;
    }

    /**
     * @return StaffResult
     */
    public function asStaffResult(): StaffResult {
        return StaffResult::valueOf($this->resultString());
    }
}