CREATE TABLE IF NOT EXISTS players_registered (rowId INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(16), xuid TEXT, firstAddress TEXT, lastAddress TEXT);

CREATE TABLE IF NOT EXISTS staffutils_ban (rowId INT PRIMARY KEY AUTO_INCREMENT, xuid TEXT, who TEXT, address TEXT, isIp BOOLEAN, reason TEXT, createdAt VARCHAR(60), endAt VARCHAR(60));

CREATE TABLE IF NOT EXISTS staffutils_mute (rowId INT PRIMARY KEY AUTO_INCREMENT, xuid TEXT, who TEXT, address TEXT, isIp BOOLEAN, reason TEXT, createdAt VARCHAR(60), endAt VARCHAR(60));