<?php

declare(strict_types=1);

namespace staffutils\command;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\listener\invmenu\MenuInventoryListener;
use staffutils\listener\invmenu\PlayerInventoryListener;
use staffutils\StaffUtils;

final class EnderChestSeeCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param string[]      $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'Run this command in-game');

            return;
        }

        if (!$this->testPermission($sender)) return;

        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($args[0])) === null) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $args[0]));

            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST)->setName($target->getName() . '\'s ender chest');

        $target->getEnderInventory()->getListeners()->add($inventoryListener = new PlayerInventoryListener($menu->getInventory()));

        $menu->getInventory()->setContents($target->getEnderInventory()->getContents());

        $menu->getInventory()->getListeners()->add(new MenuInventoryListener(
            [MenuInventoryListener::INVENTORY_INDEX => $target->getEnderInventory()],
            [MenuInventoryListener::INVENTORY_INDEX => $inventoryListener]
        ));

        $menu->send($sender);

        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use($inventoryListener, $target): void {
            $target->getEnderInventory()->getListeners()->remove($inventoryListener);
        });
    }
}