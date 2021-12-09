<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class BanCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!isset($args[0])) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', '<player>', $args[0]));

            return;
        }

        TaskUtils::runAsync(new LoadPlayerStorageAsync($args[0]), function (LoadPlayerStorageAsync $query) use($sender, $args): void {
            $result = $query->getResult();

            if (!is_array($result) || empty($result)) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', '<player>', $args[0]));

                return;
            }
        });
    }
}