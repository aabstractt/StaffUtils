<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\async\ProcessUnbanAsync;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class UnbanCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (($name = array_shift($args)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /unban <player>');

            return;
        }

        /*$xuid = $sender->getName();
        if ($sender instanceof Player) {
            $xuid = $sender->getXuid();
        }*/

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (LoadPlayerStorageAsync $query) use ($name, $sender): void {
                if (!is_array($result = $query->getResult()) || empty($result)) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processUnban($sender, $result['xuid'], $result['lastAddress'], $name);
            });

            return;
        }

        $this->processUnban($sender, $target->getXuid(), $target->getNetworkSession()->getIp(), $target->getName());
    }

    private function processUnban(CommandSender $sender, string $xuid, string $lastAddress, string $name): void {
        TaskUtils::runAsync(new ProcessUnbanAsync($xuid, $lastAddress), function (ProcessUnbanAsync $query) use ($sender, $name): void {
            if ($query->resultString() === StaffResult::UNBAN_FAIL()->name()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_UNBAN_FAIL', $name));

                return;
            }

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_UNBANNED', $name, $sender->getName()));
        });
    }
}