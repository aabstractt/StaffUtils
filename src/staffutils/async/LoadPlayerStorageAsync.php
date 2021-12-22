<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadPlayerStorageAsync extends QueryAsyncTask {

    /**
     * @param string $xuid
     * @param bool   $isXuid
     */
    public function __construct(
        private string $xuid,
        private bool $isXuid = true
    ) {}

    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement('SELECT * FROM players_registered WHERE ' . ($this->isXuid ? 'xuid' : 'username') . " = '?'");
        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        $result = $stmt->get_result();

        if (!$result instanceof mysqli_result) {
            throw new RuntimeException('Result problem');
        }

        while ($data = $result->fetch_array(MYSQLI_ASSOC)) {
            $this->setResult($data);
        }

        $result->close();
        $stmt->close();
    }
}