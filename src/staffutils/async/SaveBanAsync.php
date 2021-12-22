<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\BanEntry;
use staffutils\utils\MySQL;

class SaveBanAsync extends LoadBanActiveAsync {

    /**
     * @param BanEntry $entry
     */
    public function __construct(private BanEntry $entry) {
        parent::__construct($entry->getXuid());
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $entry = $this->entry;

        parent::query($mysqli);

        if (($result = $this->getResult()) instanceof BanEntry && $result->expired()) {
            $this->setResult('ALREADY_BANNED');

            return;
        }

        $mysqli->prepareStatement("INSERT INTO staffutils_ban (xuid, who, address, isIp, reason, createdAt, endAt) VALUES ('?', '?', '?', '?', '?', '?', '?')");

        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getAddress(), $entry->isIp(), $entry->getReason(), $entry->getCreatedAt(), $entry->getEndAt());

        $mysqli->executeStatement()->close();

        $this->setResult('SUCCESS_BANNED');
    }
}