<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\async\ProcessUnmuteAsync;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class UnmuteCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.unmute');
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
            $sender->sendMessage(TextFormat::RED . 'Usage: /unmute <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (LoadPlayerStorageAsync $query) use ($name, $sender): void {
                if (!is_array($result = $query->getResult()) || empty($result)) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processUnmute($sender, $result['xuid'], $result['lastAddress'], $result['username']);
            });

            return;
        }

        $this->processUnmute($sender, $target->getXuid(), $target->getNetworkSession()->getIp(), $target->getName());
    }

    private function processUnmute(CommandSender $sender, string $xuid, string $lastAddress, string $name): void {
        TaskUtils::runAsync(new ProcessUnmuteAsync($xuid, $lastAddress), function (ProcessUnmuteAsync $query) use ($sender, $name): void {
            if ($query->asStaffResult() === StaffResult::UNMUTE_FAIL()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_UNMUTE_FAIL', $name));

                return;
            }

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_UNMUTED', $name, $sender->getName()));
        });
    }
}