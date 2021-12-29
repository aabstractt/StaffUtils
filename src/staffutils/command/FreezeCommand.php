<?php

declare(strict_types=1);

namespace staffutils\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class FreezeCommand extends Command {

    /** @var array */
    public static array $freezedPlayers = [];

    /**
     * @param string                   $name
     * @param Translatable|string      $description
     * @param Translatable|string|null $usageMessage
     * @param array                    $aliases
     */
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('staffutils.command.freeze');
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this in-game');

            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        if (($name = array_shift($args)) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($name)) === null) {
            $sender->sendMessage(TextFormat::RED . 'Player not found');

            return;
        }

        if (in_array($target->getName(), self::$freezedPlayers, true)) {
            self::$freezedPlayers = array_diff(self::$freezedPlayers, [$target->getName()]);

            $sender->sendMessage(TextFormat::GREEN . $target->getName() . ' was unfreezed');

            return;
        }

        self::$freezedPlayers[] = $target->getName();

        $target->sendMessage(TextFormat::RED . 'You now was freezed!');
        $sender->sendMessage(TextFormat::GREEN . $target->getName() . TextFormat::BLUE . ' was freezed successfully.');
    }
}