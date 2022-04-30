<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\async\ProcessUnbanAsync;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\TaskUtils;

class UnbanCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.unban');
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (($name = array_shift($args)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /unban <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (QueryAsyncTask $query) use ($name, $sender): void {
                if (!is_array($result = $query->getResult()) || count($result) === 0) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processUnban($sender, $result['xuid'], $result['lastAddress'], $result['username']);
            });

            return;
        }

        $this->processUnban($sender, $target->getXuid(), $target->getNetworkSession()->getIp(), $target->getName());
    }

    private function processUnban(CommandSender $sender, string $xuid, string $lastAddress, string $name): void {
        TaskUtils::runAsync(new ProcessUnbanAsync($xuid, $lastAddress), function (QueryAsyncTask $query) use ($sender, $name): void {
            if ($query->asStaffResult() === StaffResult::UNBAN_FAIL()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_UNBAN_FAIL', $name));

                return;
            }

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_UNBANNED', $name, $sender->getName()));
        });
    }
}