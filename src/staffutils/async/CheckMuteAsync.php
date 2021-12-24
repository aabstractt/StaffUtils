<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\utils\MySQL;
use staffutils\utils\TaskUtils;

class CheckMuteAsync extends LoadMuteActiveAsync {

    public bool $cancel = false;

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        parent::query($mysqli);

        if (($result = $this->entryResult()) === null || $result->expired()) {
            return;
        }

        $this->cancel = true;
    }

    public function onCompletion(): void {
        if (($entry = $this->entryResult()) !== null && $entry->expired()) {
            TaskUtils::runAsync(new ProcessUnmuteExpiredAsync($entry->getRowId()));
        }

        parent::onCompletion();
    }
}