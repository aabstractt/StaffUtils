<?php

declare(strict_types=1);

namespace staffutils\task;

use Exception;
use ReflectionClass;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
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
        TaskUtils::submitAsync($this);

        if ($this->logException !== null) {
            Server::getInstance()->getLogger()->critical("Could not execute asynchronous task " . (new ReflectionClass($this))->getShortName() . ": Task crashed");

            Server::getInstance()->getLogger()->logException($this->logException);
        }
    }

    /**
     * @param string $table
     * @param array  $data
     *
     * @return string
     */
    protected static function arrayToQueryInsert(string $table, array $data): string {
        $query = 'INSERT INTO ' . $table . ' (';

        $i = 0;

        foreach (array_keys($data) as $key) {
            if ($i !== (count($data) - 1)) {
                $query .= "$key, ";
            } else {
                $query .= $key;
            }

            $i++;
        }

        $query .= ') VALUES (';

        $i = 0;

        foreach ($data as $value) {
            if (is_array($value)) {
                $value = implode(';', $value);
            } else if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }

            if ($i !== (count($data) - 1)) {
                $query .= "'$value', ";
            } else {
                $query .= "'$value'";
            }

            $i++;
        }

        $query .= ')';

        return $query;
    }

    /**
     * @param string $table
     * @param array  $data
     * @param string $option
     *
     * @return string
     */
    protected static function arrayToQueryUpdate(string $table, array $data, string $option): string {
        $query = 'UPDATE ' . $table . ' ';

        $i = 0;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(';', $value);
            } else if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }

            if (count($data) === 1) {
                $query .= "SET $key = '$value' ";
            } else if ($i === 0) {
                $query .= "SET $key = '$value', ";
            } else if ($i !== (count($data) - 1)) {
                $query .= "$key = '$value', ";
            } else {
                $query .= "$key = '$value'";
            }

            $i++;
        }

        return $query . ' ' . $option;
    }
}