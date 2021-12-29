<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\async\ProcessWarnAsync;
use staffutils\BanEntry;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class WarnCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.warn');
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
            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' <player> <reason>');

            return;
        }

        $xuid = $sender->getName();
        if ($sender instanceof Player) {
            $xuid = $sender->getXuid();
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (LoadPlayerStorageAsync $query) use($name, $xuid, $sender, $args): void {
                $result = $query->getResult();

                if (!is_array($result) || count($result) === 0) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processWarn($sender, $args, new BanEntry($result['xuid'], $result['username'], $result['lastAddress'], $xuid, $sender->getName(), false));
            });

            return;
        }

        $this->processWarn($sender, $args, new BanEntry($target->getXuid(), $target->getName(), $target->getNetworkSession()->getIp(), $xuid, $sender->getName(), false));
    }

    /**
     * @param CommandSender $sender
     * @param array         $args
     * @param BanEntry      $entry
     */
    private function processWarn(CommandSender $sender, array $args, BanEntry $entry): void {
        if (count($args) === 0) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('INVALID_REASON'));

            return;
        }

        $entry->setReason(implode(' ', $args));

        $entry->setCreatedAt();
        $entry->setType(BanEntry::WARN_TYPE);

        TaskUtils::runAsync(new ProcessWarnAsync($entry), function (ProcessWarnAsync $query) use ($sender, $entry): void {
            if ($query->asStaffResult() === StaffResult::ALREADY_WARNED()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_ALREADY_WARNED', $entry->getName()));

                return;
            }

            StaffUtils::sendDiscordMessage(StaffUtils::replacePlaceholders('DISCORD_MESSAGE', $sender->getName(), $entry->getName(), $entry->typeToString(), '', $entry->getReason()));

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_WARNED', $entry->getName(), $sender->getName(), $entry->getReason()));
        });
    }
}