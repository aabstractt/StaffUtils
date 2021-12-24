<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
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
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
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

                if (!is_array($result) || empty($result)) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->processMute($sender, $args, new BanEntry($result['xuid'], $result['username'], $result['lastAddress'], $xuid, $sender->getName(), $commandLabel === 'ipban'));
            });

            return;
        }

        $this->processMute($sender, $args, new BanEntry($target->getXuid(), $target->getName(), $target->getNetworkSession()->getIp(), $xuid, $sender->getName(), $commandLabel === 'ipban'));
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

        $endAt = '';

        if ($time !== null) {
            $endAt = StaffUtils::dateNow($time);
        }

        if (empty($args) && StaffUtils::getInstance()->getConfig()->get('require_mute_reason', true)) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('INVALID_REASON'));

            return;
        }

        $entry->setReason(empty($args) ? StaffUtils::replacePlaceholders('DEFAULT_MUTE_REASON') : implode(' ', $args));

        $entry->setCreatedAt();
        $entry->setEndAt($endAt);
        $entry->setType(BanEntry::BAN_TYPE);

        TaskUtils::runAsync(new ProcessMuteAsync($entry, boolval(StaffUtils::getInstance()->getConfig()->get('bypass_already_muted', true))), function (ProcessMuteAsync $query) use ($timeString, $sender, $entry): void {
            if ($query->asStaffResult() === StaffResult::ALREADY_MUTED()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_ALREADY_MUTED', $entry->getName()));

                return;
            }

            //$this->kickBan($entry);

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_MUTED', $entry->getName(), $sender->getName(), $entry->getReason(), StaffUtils::timeRemaining($timeString) ?? ''));
        });
    }
}