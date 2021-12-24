<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadAddressStorageAsync;
use staffutils\async\LoadPlayerStorageAsync;
use staffutils\StaffUtils;
use staffutils\utils\TaskUtils;

class AltsCommand extends Command {

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.alts');
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
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            TaskUtils::runAsync(new LoadPlayerStorageAsync($name, false), function(LoadPlayerStorageAsync $query) use ($sender, $name): void {
                if (!is_array($result = $query->getResult()) || empty($result)) {
                    $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                    return;
                }

                self::processAlts($sender, $result['username'], $result['xuid'], $result['lastAddress']);
            });

            return;
        }

        self::processAlts($sender, $target->getName(), $target->getXuid(), $target->getNetworkSession()->getIp());
    }

    /**
     * @param CommandSender|null $sender
     * @param string             $name
     * @param string             $xuid
     * @param string             $lastAddress
     * @param bool               $nameEquals
     */
    public static function processAlts(?CommandSender $sender, string $name, string $xuid, string $lastAddress, bool $nameEquals = false): void {
        TaskUtils::runAsync(new LoadAddressStorageAsync($xuid, $lastAddress), function (LoadAddressStorageAsync $query) use ($nameEquals, $sender, $name): void {
            if (!is_array($result = $query->getResult()) || empty($result)) {
                $sender?->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $name));

                return;
            }

            if ($nameEquals) {
                $maxBans = StaffUtils::getInstance()->getConfig()->getNested('notify.dupeip_on_join_threshold', 0);

                if ($maxBans > 0 && $maxBans > count($query->banned)) {
                    return;
                }
            }

            $scanners = [];
            foreach ($result[0] as $scanner) {
                $key = 'SCANNING_OFFLINE';

                if (in_array($scanner, $result[1])) {
                    $key = 'SCANNING_BANNED';
                } else if (Server::getInstance()->getPlayerExact($scanner) !== null) {
                    $key = 'SCANNING_ONLINE';
                }

                $scanners[] = StaffUtils::replacePlaceholders($key, $scanner);
            }

            if ($sender !== null) {
                $sender->sendMessage(StaffUtils::replacePlaceholders('SCANNING_PLAYER', $name));
                $sender->sendMessage(implode(TextFormat::WHITE . ', ', $scanners));

                return;
            }

            $filter = array_filter(Server::getInstance()->getOnlinePlayers(), function ($player) {
                return $player->hasPermission('staffutils.permission');
            });

            foreach ($filter as $target) {
                $target->sendMessage(StaffUtils::replacePlaceholders('SCANNING_PLAYER', $name));
                $target->sendMessage(implode(TextFormat::WHITE . ', ', $scanners));
            }

            Server::getInstance()->getLogger()->info(StaffUtils::replacePlaceholders('SCANNING_PLAYER', $name));
            Server::getInstance()->getLogger()->info(implode(TextFormat::WHITE . ', ', $scanners));
        });
    }
}