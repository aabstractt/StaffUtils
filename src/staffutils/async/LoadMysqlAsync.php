<?php

declare(strict_types=1);

namespace staffutils\async;

use staffutils\task\QueryAsyncTask;
use staffutils\utils\MySQL;

final class LoadMysqlAsync extends QueryAsyncTask {

    /**
     * @param MySQL $mysqli
     */
    public function query(MySQL $mysqli): void {
        $stmt = $mysqli->executeStatement('CREATE TABLE IF NOT EXISTS players_registered (rowId INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(16), xuid TEXT, firstAddress TEXT, lastAddress TEXT)');
        $stmt->close();

        $stmt = $mysqli->executeStatement('CREATE TABLE IF NOT EXISTS staffutils_ban (rowId INT PRIMARY KEY AUTO_INCREMENT, xuid TEXT, who TEXT, address TEXT, isIp BOOLEAN, reason TEXT, createdAt VARCHAR(60), endAt VARCHAR(60))');
        $stmt->close();

        $stmt = $mysqli->executeStatement('CREATE TABLE IF NOT EXISTS staffutils_mute (rowId INT PRIMARY KEY AUTO_INCREMENT, xuid TEXT, who TEXT, address TEXT, isIp BOOLEAN, reason TEXT, createdAt VARCHAR(60), endAt VARCHAR(60))');
        $stmt->close();

        $stmt = $mysqli->executeStatement('CREATE TABLE IF NOT EXISTS staffutils_warn (rowId INT PRIMARY KEY AUTO_INCREMENT, xuid TEXT, who TEXT, reason TEXT)');
        $stmt->close();
    }
}