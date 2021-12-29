<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use staffutils\command\FreezeCommand;
use staffutils\command\VanishCommand;
use staffutils\StaffUtils;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        if (in_array($player->getName(), FreezeCommand::$freezedPlayers, true)) {
            FreezeCommand::$freezedPlayers = array_diff(FreezeCommand::$freezedPlayers, [$player->getName()]);
        }

        if (in_array($player->getName(), VanishCommand::$vanish, true)) {
            VanishCommand::$vanish = array_diff(VanishCommand::$vanish, [$player->getName()]);
        }

        if (!isset(StaffUtils::$results[$player->getName()])) {
            return;
        }

        unset(StaffUtils::$results[$player->getName()]);
    }
}