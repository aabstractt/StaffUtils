<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use staffutils\async\CheckMuteAsync;
use staffutils\StaffUtils;
use staffutils\task\QueryAsyncTask;
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

        TaskUtils::runAsync(new CheckMuteAsync($player->getXuid(), $player->getNetworkSession()->getIp()), function (QueryAsyncTask $query) use ($format, $player): void {
            if (($entry = $query->entryResult()) === null || $entry->expired()) {
                Server::getInstance()->broadcastMessage($format);

                return;
            }

            $player->sendMessage(StaffUtils::replacePlaceholders('PLAYER_TRIED_SPEAK', $entry->getWhoName(), $entry->getReason(), $entry->remainingDurationString()));

            Server::getInstance()->getLogger()->info($message = StaffUtils::replacePlaceholders('PLAYER_TRIED_SPEAK_MUTED', $player->getName(), $entry->remainingDurationString()));

            foreach (array_filter(
                         Server::getInstance()->getOnlinePlayers(),
                         fn(Player $player) => $player->hasPermission('staffutils.permission')
                     ) as $target) {
                $target->sendMessage($message);
            }
        });

        $ev->cancel();
    }
}