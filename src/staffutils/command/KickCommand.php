<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\StaffUtils;

class KickCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (($name = array_shift($args)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' <player> <?reason>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

            return;
        }

        $target->kick(StaffUtils::replacePlaceholders('PLAYER_KICK', $sender->getName(), implode(' ', $args)));

        Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_KICKED', $target->getName(), $sender->getName(), implode(' ', $args)));
    }
}