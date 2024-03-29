<?php

declare(strict_types=1);

namespace staffutils\async;

use mysqli_result;
use RuntimeException;
use staffutils\BanEntry;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

class LoadBanActiveAsync extends QueryAsyncTask {

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
        if (($entry = $this->fetch($mysqli, $this->lastAddress, false)) === null) {
            $entry = $this->fetch($mysqli, $this->xuid);
        }

        $this->setResult($entry);
    }

    protected function fetch(MySQL $mysqli, string $value, bool $isXuid = true): ?BanEntry {
        $mysqli->prepareStatement("SELECT * FROM staffutils_ban WHERE " . ($isXuid ? 'xuid' : "isIp = '1' AND address") . " = '?'");
        $mysqli->set($value);

        $stmt = $mysqli->executeStatement();

        if (!($result = $stmt->get_result()) instanceof mysqli_result) {
            throw new RuntimeException($mysqli->error);
        }

        $row = $result->fetch_array(MYSQLI_ASSOC);

        $result->close();
        $stmt->close();

        if ($row === null || count($row) === 0) {
            return null;
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
            return null;
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
                return null;
            }

            $who = $whoFetch['username'];
        }

        return new BanEntry($row['xuid'], $fetch['username'], $row['address'], $row['who'], $who, $row['isIp'] === 1, $row['reason'], $row['createdAt'], $row['endAt'], BanEntry::BAN_TYPE, $row['rowId']);
    }
}