<?php

declare(strict_types=1);

namespace staffutils\async;

use pocketmine\scheduler\AsyncTask;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadPlayerStorageAsync extends QueryAsyncTask {

    public function __construct(
        private string $name
    ) {}

    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement('SELECT * FROM players_registered WHERE username = ?');

        $mysqli->set($this->name);

        $stmt = $mysqli->executeStatement();

        $result = $stmt->get_result();

        if (!$result instanceof \mysqli_result) {
            throw new \RuntimeException('Result problem');
        }

        while ($data = $result->fetch_array(MYSQLI_ASSOC)) {
            $this->setResult($data);
        }

        $result->close();
        $stmt->close();
    }
}