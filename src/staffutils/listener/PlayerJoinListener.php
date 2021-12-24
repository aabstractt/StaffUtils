<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use staffutils\command\AltsCommand;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class PlayerJoinListener implements Listener {

    /**
     * @param PlayerJoinEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerJoinEvent(PlayerJoinEvent $ev): void {
        $player = $ev->getPlayer();

        AltsCommand::processAlts(null, $player->getName(), $player->getXuid(), $player->getNetworkSession()->getIp(), true);

        if (($entry = StaffUtils::$results[$player->getName()] ?? null) === null) {
            return;
        }

        if (StaffUtils::getInstance()->getConfig()->getNested('notify.banned_player_join', true)) {
            $filter = array_filter(Server::getInstance()->getOnlinePlayers(), function ($player) {
                return $player->hasPermission('staffutils.permission');
            });

            Server::getInstance()->getLogger()->info($message = StaffUtils::replacePlaceholders('PLAYER_TRIED_JOIN_BANNED', $entry->getName(), $entry->remainingDurationString()));

            foreach ($filter as $target) {
                $target->sendMessage($message);
            }
        }

        // TODO: Later to show the kick message
        TaskUtils::runLater(new ClosureTask(function () use($entry, $player): void {
            $player->kick(StaffUtils::replacePlaceholders('PLAYER_KICK_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_BANNED', $entry->getWhoName(), $entry->getReason(), $entry->getCreatedAt(), $entry->remainingDurationString()));
        }), 5);
    }
}