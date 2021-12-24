<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;
use staffutils\async\CheckMuteAsync;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class PlayerChatListener implements Listener {

    /**
     * @param PlayerChatEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerChatEvent(PlayerChatEvent $ev): void {
        $player = $ev->getPlayer();

        $format = $ev->getFormat();

        TaskUtils::runAsync(new CheckMuteAsync($player->getXuid(), $player->getNetworkSession()->getIp()), function (CheckMuteAsync $query) use ($format, $player): void {
            if (!$query->cancel || ($entry = $query->entryResult()) === null) {
                Server::getInstance()->broadcastMessage($format);
                return;
            }

            $player->sendMessage(StaffUtils::replacePlaceholders('PLAYER_TRIED_SPEAK', $entry->getWhoName(), $entry->getReason(), $entry->remainingDurationString()));

            $filter = array_filter(Server::getInstance()->getOnlinePlayers(), function ($player) {
                return $player->hasPermission('staffutils.permission');
            });

            Server::getInstance()->getLogger()->info($message = StaffUtils::replacePlaceholders('PLAYER_TRIED_SPEAK_MUTED', $player->getName(), $entry->remainingDurationString()));

            foreach ($filter as $target) {
                $target->sendMessage($message);
            }
        });

        $ev->cancel();
    }
}