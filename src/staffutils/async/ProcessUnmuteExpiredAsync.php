<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class ProcessUnmuteExpiredAsync extends QueryAsyncTask {

    /**
     * @param int $rowId
     */
    public function __construct(
        private int $rowId
    ) {}

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement("DELETE FROM staffutils_mute WHERE rowId = '?'");
        $mysqli->set($this->rowId);

        $mysqli->executeStatement()->close();
    }
}