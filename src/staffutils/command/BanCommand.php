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
use staffutils\async\ProcessBanAsync;
use staffutils\BanEntry;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\TaskUtils;

class BanCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.ban');
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
            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' <player> <time> <reason>');

            return;
        }

        $xuid = $sender instanceof Player ? $sender->getXuid() : $sender->getName();

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (QueryAsyncTask $query) use($commandLabel, $name, $xuid, $sender, $args): void {
                if (!is_array($result = $query->getResult()) || count($result) === 0) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->insertBan($sender, $args, new BanEntry($result['xuid'], $result['username'], $result['lastAddress'], $xuid, $sender->getName(), $commandLabel === 'ipban'));
            });

            return;
        }

        $this->insertBan($sender, $args, new BanEntry($target->getXuid(), $target->getName(), $target->getNetworkSession()->getIp(), $xuid, $sender->getName(), $commandLabel === 'ipban'));
    }

    /**
     * @param CommandSender $sender
     * @param array         $args
     * @param BanEntry      $entry
     */
    private function insertBan(CommandSender $sender, array $args, BanEntry $entry): void {
        $time = null;

        $timeString = '';
        if (count($args) > 0 && ($time = StaffUtils::calculateTime(($timeString = $args[0]))) !== null) {
            unset($args[0]);
        }

        if (!is_string($maxString = StaffUtils::getInstance()->getConfig()->getNested('durations.tempban_max', '7d'))) {
            return;
        }

        if (($maxTime = StaffUtils::calculateTime($maxString)) === null) {
            return;
        }

        if (!$sender->hasPermission('staffutils.unlimited.ban') && ($time === null || $time > $maxTime)) {
            $time = $maxTime;

            $timeString = $maxString;
        }

        if ($time !== null) {
            $entry->setEndAt(StaffUtils::dateNow($time));
        }

        if (count($args) === 0 && StaffUtils::getInstance()->getBoolean('require_ban_reason', true)) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('INVALID_REASON'));

            return;
        }

        $entry->setReason(count($args) === 0 ? StaffUtils::replacePlaceholders('DEFAULT_BAN_REASON') : implode(' ', $args));

        $entry->setCreatedAt();
        $entry->setType(BanEntry::BAN_TYPE);

        TaskUtils::runAsync(new ProcessBanAsync($entry, StaffUtils::getInstance()->getBoolean('bypass_already_banned', true)), function (QueryAsyncTask $query) use ($timeString, $sender, $entry): void {
            if ($query->asStaffResult() === StaffResult::ALREADY_BANNED()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_ALREADY_BANNED', $entry->getName()));

                return;
            }

            StaffUtils::sendDiscordMessage(StaffUtils::replacePlaceholders('DISCORD_MESSAGE', $sender->getName(), $entry->getName(), $entry->typeToString(), StaffUtils::timeRemaining($timeString) ?? '', $entry->getReason()));

            $this->kickBan($entry);

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_BANNED', $entry->getName(), $sender->getName(), $entry->getReason(), StaffUtils::timeRemaining($timeString) ?? ''));
        });
    }

    /**
     * @param BanEntry $entry
     */
    private function kickBan(BanEntry $entry): void {
        $message = StaffUtils::replacePlaceholders('PLAYER_KICK_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_BANNED', $entry->getWhoName(), $entry->getReason(), $entry->getCreatedAt(), $entry->remainingDurationString());

        if ($entry->isIp()) {
            $filter = array_filter(Server::getInstance()->getOnlinePlayers(), function ($player) use ($entry) {
                return $player->getNetworkSession()->getIp() === $entry->getAddress();
            });

            foreach ($filter as $target) {
                $target->kick($message);
            }

            return;
        }

        if (($target = Server::getInstance()->getPlayerExact($entry->getName())) !== null) {
            $target->kick($message);
        }
    }
}