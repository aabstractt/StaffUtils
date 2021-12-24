<?php

declare(strict_types=1);

namespace staffutils\async;

use LogicException;
use staffutils\BanEntry;
use staffutils\utils\MySQL;
use staffutils\utils\TaskUtils;

class ProcessMuteAsync extends LoadMuteActiveAsync {

    /**
     * @param BanEntry|null $entry
     * @param bool          $bypass_already_muted
     */
    public function __construct(
        private ?BanEntry $entry,
        private bool $bypass_already_muted
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
            if (!$result->expired() && !$this->bypass_already_muted) {
                $this->setResult(['ALREADY_MUTED', $result]);

                return;
            }

            $this->entry = $result;
        }

        $this->setResult('SUCCESS_MUTED');

        $mysqli->prepareStatement("INSERT INTO staffutils_mute (xuid, who, address, isIp, reason, createdAt, endAt) VALUES ('?', '?', '?', '?', '?', '?', '?')");
        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getAddress(), $entry->isIp(), $entry->getReason(), $entry->getCreatedAt(), $entry->getEndAt());

        $mysqli->executeStatement()->close();
    }

    public function onCompletion(): void {
        if (($entry = $this->entry) !== null) {
            TaskUtils::runAsync(new ProcessUnmuteExpiredAsync($entry->getRowId()));
        }

        parent::onCompletion();
    }
}