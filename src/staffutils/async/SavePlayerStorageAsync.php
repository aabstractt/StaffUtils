<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class SavePlayerStorageAsync extends QueryAsyncTask {

    /**
     * @param string      $name
     * @param string      $xuid
     * @param string      $firstAddress
     * @param string|null $lastAddress
     */
    public function __construct(
        private string $name,
        private string $xuid,
        private string $firstAddress,
        private ?string $lastAddress = null
    ) {}

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement('SELECT * FROM players_registered WHERE xuid = ?');

        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException('Result problem');
        }

        if (is_array($result->fetch_array(MYSQLI_ASSOC))) {
            $mysqli->prepareStatement('UPDATE players_registered SET username = ?, lastAddress = ? WHERE xuid = ?');

            $mysqli->set($this->name, $this->lastAddress, $this->xuid);
        } else {
            $mysqli->prepareStatement('INSERT INTO players_registered (username, xuid, firstAddress, lastAddress) VALUES (?, ?, ?, ?)');

            $mysqli->set($this->name, $this->xuid, $this->firstAddress, $this->lastAddress ?? $this->firstAddress);
        }

        $stmt->close();

        $mysqli->executeStatement()->close();
    }
}