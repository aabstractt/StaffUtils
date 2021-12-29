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
use staffutils\async\ProcessMuteAsync;
use staffutils\BanEntry;
use staffutils\StaffResult;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class MuteCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.mute');
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

        $xuid = $sender->getName();
        if ($sender instanceof Player) {
            $xuid = $sender->getXuid();
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (LoadPlayerStorageAsync $query) use($commandLabel, $name, $xuid, $sender, $args): void {
                $result = $query->getResult();

                if (!is_array($result) || count($result) === 0) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processMute($sender, $args, new BanEntry($result['xuid'], $result['username'], $result['lastAddress'], $xuid, $sender->getName(), $commandLabel === 'ipmute'));
            });

            return;
        }

        $this->processMute($sender, $args, new BanEntry($target->getXuid(), $target->getName(), $target->getNetworkSession()->getIp(), $xuid, $sender->getName(), $commandLabel === 'ipmute'));
    }

    /**
     * @param CommandSender $sender
     * @param array         $args
     * @param BanEntry      $entry
     */
    private function processMute(CommandSender $sender, array $args, BanEntry $entry): void {
        $time = null;

        $timeString = '';
        if (count($args) > 0 && ($time = StaffUtils::calculateTime(($timeString = $args[0]))) !== null) {
            unset($args[0]);
        }

        if (!is_string($maxString = StaffUtils::getInstance()->getConfig()->getNested('durations.tempmute_max', '7d'))) {
            return;
        }

        if (($maxTime = StaffUtils::calculateTime($maxString)) === null) {
            return;
        }

        if (!$sender->hasPermission('staffutils.unlimited.mute') && ($time === null || $time > $maxTime)) {
            $time = $maxTime;

            $timeString = $maxString;
        }

        if ($time !== null) {
            $entry->setEndAt(StaffUtils::dateNow($time));
        }

        if (!is_bool($required = StaffUtils::getInstance()->getConfig()->get('require_mute_reason', true))) {
            return;
        }

        if (count($args) === 0 && $required) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('INVALID_REASON'));

            return;
        }

        $entry->setReason(count($args) === 0 ? StaffUtils::replacePlaceholders('DEFAULT_MUTE_REASON') : implode(' ', $args));

        $entry->setCreatedAt();
        $entry->setType(BanEntry::MUTE_TYPE);

        TaskUtils::runAsync(new ProcessMuteAsync($entry, boolval(StaffUtils::getInstance()->getConfig()->get('bypass_already_muted', true))), function (ProcessMuteAsync $query) use ($timeString, $sender, $entry): void {
            if ($query->asStaffResult() === StaffResult::ALREADY_MUTED()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_ALREADY_MUTED', $entry->getName()));

                return;
            }

            StaffUtils::sendDiscordMessage(StaffUtils::replacePlaceholders('DISCORD_MESSAGE', $sender->getName(), $entry->getName(), $entry->typeToString(), StaffUtils::timeRemaining($timeString) ?? '', $entry->getReason()));

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_MUTED', $entry->getName(), $sender->getName(), $entry->getReason(), StaffUtils::timeRemaining($timeString) ?? ''));
        });
    }
}