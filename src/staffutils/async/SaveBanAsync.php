<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\BanEntry;
use staffutils\utils\BanResult;
use staffutils\utils\MySQL;

class SaveBanAsync extends LoadBanActiveAsync {

    public BanResult $banResult;

    /**
     * @param BanEntry $entry
     */
    public function __construct(BanEntry $entry) {
        parent::__construct($entry->getXuid());

        $this->setResult($entry);
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        /** @var BanEntry $entry */
        $entry = $this->getResult();

        parent::query($mysqli);

        /** @var $result BanEntry */
        if (($result = $this->getResult()) instanceof BanEntry && $result->expired()) {
            $this->banResult = BanResult::ALREADY_BANNED();

            return;
        }

        $mysqli->prepareStatement('INSERT INTO staffutils_ban (xuid, who, address, isIp, reason, createdAt, endAt) VALUES (?, ?, ?, ?, ?, ?, ?)');

        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getAddress(), $entry->isIp(), $entry->getReason(), $entry->getCreatedAt(), $entry->getEndAt());

        $mysqli->executeStatement()->close();

        $this->banResult = BanResult::SUCCESS();
    }
}