<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\BanEntry;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class ProcessWarnAsync extends QueryAsyncTask {

    /**
     * @param BanEntry $entry
     */
    public function __construct(BanEntry $entry) {
        $this->storeLocal('BAN_ENTRY', $entry);
    }

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        if (!($entry = $this->fetchLocal('BAN_ENTRY')) instanceof BanEntry) {
            $this->setResult('ALREADY_WARNED');

            return;
        }

        $this->setResult('SUCCESS_WARNED');

        $mysqli->prepareStatement("INSERT INTO staffutils_warn (xuid, who, reason) VALUES ('?', '?', '?')");
        $mysqli->set($entry->getXuid(), $entry->getWho(), $entry->getReason());

        $mysqli->executeStatement()->close();
    }
}