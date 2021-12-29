<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\BanEntry;
use staffutils\utils\MySQL;

class ProcessWarnAsync extends LoadWarnActiveAsync {

    /**
     * @param BanEntry $entry
     */
    public function __construct(
        private BanEntry $entry
    ) {
        parent::__construct($entry->getXuid(), $entry->getAddress());
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        parent::query($mysqli);

        if (($result = $this->entryResult()) !== null && !$result->expired()) {
            $this->setResult(['ALREADY_WARNED', $result]);

            return;
        }

        $entry = $this->entry;

        $this->setResult('SUCCESS_WARNED');

        $mysqli->prepareStatement("INSERT INTO staffutils_warn (xuid, who, reason) VALUES ('?', '?', '?')");
        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getReason());

        $mysqli->executeStatement()->close();
    }
}