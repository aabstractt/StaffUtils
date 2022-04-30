<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\utils\TaskUtils;

class CheckMuteAsync extends LoadMuteActiveAsync {

    public function onCompletion(): void {
        if (($entry = $this->entryResult()) !== null && $entry->expired()) {
            TaskUtils::runAsync(new ProcessUnmuteExpiredAsync($entry->getRowId()));
        }

        parent::onCompletion();
    }
}