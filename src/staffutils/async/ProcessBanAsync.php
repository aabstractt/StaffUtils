<?php

declare(strict_types=1);

namespace staffutils\async;

use LogicException;
use staffutils\BanEntry;
use staffutils\utils\MySQL;
use staffutils\utils\TaskUtils;

class ProcessBanAsync extends LoadBanActiveAsync {

    /**
     * @param BanEntry|null $entry
     * @param bool          $bypass_already_banned
     */
    public function __construct(
        private ?BanEntry $entry,
        private bool $bypass_already_banned
    ) {
        if (($entry = $this->entry) === null) {
            throw new LogicException('BanEntry is null');
        }

        parent::__construct($entry->getXuid(), $entry->getAddress());
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        if (($entry = $this->entry) === null) {
            return;
        }

        $this->entry = null;

        parent::query($mysqli);

        if (($result = $this->entryResult()) !== null) {
            if (!$result->expired() && !$this->bypass_already_banned) {
                $this->setResult(['ALREADY_BANNED', $result]);

                return;
            }

            $this->entry = $result;
        }

        $mysqli->prepareStatement("INSERT INTO staffutils_ban (xuid, who, address, isIp, reason, createdAt, endAt) VALUES ('?', '?', '?', '?', '?', '?', '?')");
        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getAddress(), $entry->isIp(), $entry->getReason(), $entry->getCreatedAt(), $entry->getEndAt());

        $mysqli->executeStatement()->close();

        $this->setResult('SUCCESS_BANNED');
    }

    public function onCompletion(): void {
        if (($entry = $this->entry) !== null) {
            TaskUtils::runAsync(new ProcessUnbanExpiredAsync($entry->getRowId()));
        }

        parent::onCompletion();
    }
}