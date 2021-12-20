<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use staffutils\async\LoadBanActiveAsync;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\async\SaveBanAsync;
use staffutils\BanEntry;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class BanCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (($name = array_shift($args)) === null) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', '<player>', $args[0]));

            return;
        }

        $xuid = $sender->getName();
        if ($sender instanceof Player) {
            $xuid = $sender->getXuid();
        }

        $target = Server::getInstance()->getPlayerByPrefix($name);

        if ($target === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function (LoadPlayerStorageAsync $query) use($commandLabel, $name, $xuid, $sender, $args): void {
                $result = $query->getResult();

                if (!is_array($result) || empty($result)) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                $this->insertBan($sender, $args, new BanEntry($result['xuid'], $result['username'], $result['address'], $xuid, $commandLabel === 'ipban'));
            });

            return;
        }

        $this->insertBan($sender, $args, new BanEntry($target->getXuid(), $target->getName(), $target->getNetworkSession()->getIp(), $xuid, $commandLabel === 'ipban'));
    }

    private function insertBan(CommandSender $sender, array $args, BanEntry $entry): void {
        $time = null;

        $timeString = '';
        if (count($args) > 0 && ($time = StaffUtils::calculateTime(($timeString = $args[0]))) !== null) {
            unset($args[0]);
        }

        if (!is_string($maxString = StaffUtils::getInstance()->getConfig()->get('tempban_max', '7d'))) {
            return;
        }

        if (($maxTime = StaffUtils::calculateTime($maxString)) === null) {
            return;
        }

        if (!$sender->hasPermission('staffutils.unlimited.ban') && ($time === null || $time > $maxTime)) {
            $time = $maxTime;

            $timeString = $maxString;
        }

        $endAt = '';

        if ($time !== null) {
            $endAt = StaffUtils::dateNow($time);
        }

        if (count($args) > 0) {
            $entry->setReason(implode(' ', $args));
        }

        TaskUtils::runAsync(new LoadBanActiveAsync($entry->getXuid()), function (LoadBanActiveAsync $query) use ($timeString, $endAt, $entry, $sender): void {
            if (($result = $query->getResult()) instanceof BanEntry && !$result->expired()) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_ALREADY_BANNED', $result->getName()));

                return;
            }

            $entry->setCreatedAt();
            $entry->setEndAt($endAt);
            $entry->setType(BanEntry::BAN_TYPE);

            Server::getInstance()->broadcastMessage(StaffUtils::replacePlaceholders('PLAYER_' . ($entry->isPermanent() ? 'PERMANENTLY' : 'TEMPORARILY') . '_BANNED', $entry->getName(), $sender->getName(), $entry->getReason(), StaffUtils::timeRemaining($timeString)));

            TaskUtils::runAsync(new SaveBanAsync($entry));
        });
    }
}