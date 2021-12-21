<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadBanActiveAsync extends QueryAsyncTask {

    /**
     * @param string $xuid
     */
    public function __construct(
        private string $xuid
    ) {}

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        // TODO: Implement query() method.
    }
}