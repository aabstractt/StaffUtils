<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class VanishCommand extends Command {

    public static array $vanish = [];

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.vanish');
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        if (in_array($sender->getName(), self::$vanish, true)) {
            self::disableVanish($sender);

            self::$vanish = array_diff(self::$vanish, [$sender->getName()]);

            $sender->sendMessage(TextFormat::BLUE . 'Vanish mode disabled');

            return;
        }

        self::$vanish[] = $sender->getName();

        self::enableVanish($sender);

        $sender->sendMessage(TextFormat::GREEN . 'Vanish mode enabled');
    }

    /**
     * @param Player $player
     */
    public static function enableVanish(Player $player): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $target) {
            if (in_array($target->getName(), self::$vanish, true)) {
                $target->showPlayer($player);
            } else {
                $target->hidePlayer($player);
            }
        }
    }

    /**
     * @param Player $player
     */
    public static function disableVanish(Player $player): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $target) {
            $target->showPlayer($player);
        }
    }
}