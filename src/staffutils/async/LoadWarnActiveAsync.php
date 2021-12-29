<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\BanEntry;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadWarnActiveAsync extends QueryAsyncTask {

    /**
     * @param string $xuid
     * @param string $lastAddress
     */
    public function __construct(
        protected string $xuid,
        protected string $lastAddress
    ) {}

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $mysqli->prepareStatement("SELECT * FROM staffutils_warn WHERE xuid = '?'");
        $mysqli->set($this->xuid);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        $row = $result->fetch_array(MYSQLI_ASSOC);

        $result->close();
        $stmt->close();

        if ($row === null || count($row) === 0) {
            $this->setResult(null);

            return;
        }

        $mysqli->prepareStatement("SELECT * FROM players_registered WHERE xuid = '?'");
        $mysqli->set($row['xuid']);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        $fetch = $result->fetch_array(MYSQLI_ASSOC);

        $result->close();
        $stmt->close();

        if ($fetch === null || count($fetch) === 0) {
            $this->setResult(null);

            return;
        }

        if (($who = $row['who']) !== 'CONSOLE') {
            $mysqli->prepareStatement("SELECT * FROM players_registered WHERE xuid = '?'");
            $mysqli->set($who);

            $stmt = $mysqli->executeStatement();

            if (!($result = $stmt->get_result()) instanceof mysqli_result) {
                throw new RuntimeException($mysqli->error);
            }

            $whoFetch = $result->fetch_array(MYSQLI_ASSOC);

            $result->close();
            $stmt->close();

            if ($whoFetch === null || count($whoFetch) === 0) {
                $this->setResult(null);

                return;
            }

            $who = $whoFetch['username'];
        }

        $this->setResult(new BanEntry($this->xuid, $fetch['username'], '', $row['who'], $who, false, $row['reason'], '', '', BanEntry::WARN_TYPE, $row['rowId']));
    }
}