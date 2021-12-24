<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\ClosureTask;
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

        if (($entry = StaffUtils::$results[$player->getName()] ?? null) === null) {
            return;
        }

        // TODO: Later to show the kick message
        TaskUtils::runLater(new ClosureTask(function () use($entry, $player): void {
            $player->kick(StaffUtils::replacePlaceholders('PLAYER_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_BANNED', $entry->getWhoName(), $entry->getReason(), $entry->getCreatedAt(), $entry->remainingDurationString()));
        }), 5);
    }
}