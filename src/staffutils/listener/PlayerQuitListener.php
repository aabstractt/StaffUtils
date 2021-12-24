<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use staffutils\StaffUtils;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        if (!isset(StaffUtils::$results[$player->getName()])) {
            return;
        }

        unset(StaffUtils::$results[$player->getName()]);
    }
}