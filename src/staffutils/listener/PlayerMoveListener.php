<?php

declare(strict_types=1);

namespace staffutils\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use staffutils\command\FreezeCommand;

class PlayerMoveListener implements Listener {

    /**
     * @param PlayerMoveEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerMoveEvent(PlayerMoveEvent $ev): void {
        $player = $ev->getPlayer();

        if (!in_array($player->getName(), FreezeCommand::$freezedPlayers, true)) {
            return;
        }

        $ev->setTo($ev->getFrom());
    }
}