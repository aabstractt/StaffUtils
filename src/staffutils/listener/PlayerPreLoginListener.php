<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\player\XboxLivePlayerInfo;
use staffutils\async\SavePlayerStorageAsync;
use staffutils\BanEntry;
use staffutils\utils\TaskUtils;

class PlayerPreLoginListener implements Listener {

    /**
     * @param PlayerPreLoginEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerPreLoginEvent(PlayerPreLoginEvent $ev): void {
        /** @var $playerInfo XboxLivePlayerInfo */
        if (!($playerInfo = $ev->getPlayerInfo()) instanceof XboxLivePlayerInfo) {
            return;
        }

        // TODO: Check if the xuid or ip is banned and if not banned process to SavePlayerStorageAsync

        TaskUtils::runAsync(new SavePlayerStorageAsync(
            $playerInfo->getUsername(),
            $playerInfo->getXuid(),
            $ev->getIp()
        ), function (SavePlayerStorageAsync $query) use ($ev): void {
            if (($result = $query->entryResult()) === null) {
                return;
            }

            $ev->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, 'You already banned');
        });
    }
}