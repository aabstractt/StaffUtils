<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\BanEntry;
use staffutils\utils\MySQL;
use staffutils\utils\TaskUtils;

class SavePlayerStorageAsync extends LoadBanActiveAsync {

    /**
     * @param string $name
     * @param string $xuid
     * @param string $lastAddress
     */
    public function __construct(
        private string $name,
        string $xuid,
        string $lastAddress
    ) {
        parent::__construct($xuid, $lastAddress);
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        parent::query($mysqli);

        /** @var $result BanEntry */
        if (($result = $this->entryResult()) !== null && !$result->expired()) {
            return;
        }

        $mysqli->prepareStatement("SELECT * FROM players_registered WHERE xuid = '?'");

        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException('Result problem');
        }

        if (is_array($result->fetch_array(MYSQLI_ASSOC))) {
            $mysqli->prepareStatement("UPDATE players_registered SET username = '?', lastAddress = '?' WHERE xuid = '?'");

            $mysqli->set($this->name, $this->lastAddress, $this->xuid);
        } else {
            $mysqli->prepareStatement("INSERT INTO players_registered (username, xuid, firstAddress, lastAddress) VALUES ('?', '?', '?', '?')");

            $mysqli->set($this->name, $this->xuid, $this->lastAddress, $this->lastAddress);
        }

        $result->close();
        $stmt->close();

        $mysqli->executeStatement()->close();
    }

    public function onCompletion(): void {
        if (($result = $this->entryResult()) !== null && $result->expired()) {
            TaskUtils::runAsync(new ProcessUnbanExpiredAsync($result->getRowId()));

            $this->setResult(null);
        }

        parent::onCompletion();
    }
}