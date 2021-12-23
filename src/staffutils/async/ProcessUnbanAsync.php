<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\BanEntry;
use staffutils\utils\MySQL;

class ProcessUnbanAsync extends LoadBanActiveAsync {

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        parent::query($mysqli);

        /** @var $entry BanEntry */
        if (!($entry = $this->getResult()) instanceof BanEntry) {
            $this->setResult('UNBAN_FAIL');

            return;
        }

        $this->setResult('SUCCESS_UNBANNED');

        $mysqli->prepareStatement("DELETE FROM staffutils_ban WHERE " . ($entry->isIp() ? "isIp = '1' AND address" : 'xuid') . " = '?'");
        $mysqli->set(($entry->isIp() ? $this->lastAddress : $this->xuid));

        $stmt = $mysqli->executeStatement();

        $stmt->close();
    }
}