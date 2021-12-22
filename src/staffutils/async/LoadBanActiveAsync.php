<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\BanEntry;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadBanActiveAsync extends QueryAsyncTask {

    /**
     * @param string $xuid
     */
    public function __construct(
        private string $xuid
    ) {}

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement("SELECT * FROM players_registered WHERE xuid = '?'");
        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $name = (string) $row['username'];
        }

        $result->close();
        $stmt->close();

        if (!isset($name)) {
            throw new RuntimeException('Player ' . $this->xuid . ' not found');
        }

        $mysqli->prepareStatement("SELECT * FROM staffutils_ban WHERE xuid = '?'");
        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $this->setResult(new BanEntry($this->xuid, $name, $row['address'], $row['who'], $row['is_ip'] ?? false, $row['reason'], $row['createdAt'], $row['endAt'], BanEntry::BAN_TYPE));
        }

        $result->close();
        $stmt->close();
    }
}