<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\BanEntry;
use staffutils\utils\MySQL;

class LoadAddressStorageAsync extends LoadBanActiveAsync {

    /** @var BanEntry[] */
    public array $banned = [];

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement("SELECT * FROM players_registered WHERE lastAddress = '?'");
        $mysqli->set($this->lastAddress);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        $entries = [];
        $banned = [];

        while ($fetch = $result->fetch_array(MYSQLI_ASSOC)) {
            $entries[] = $fetch['username'];

            if ($this->fetch($mysqli, $fetch['xuid']) === null && $this->fetch($mysqli, $fetch['lastAddress'], false) === null) {
                continue;
            }

            $banned[] = $fetch['username'];
        }

        $this->setResult([$entries, $banned]);
    }
}