<?php

declare(strict_types=1);

namespace staffutils\command;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use staffutils\listener\invmenu\MenuInventoryListener;
use staffutils\listener\invmenu\PlayerArmorInventoryListener;
use staffutils\listener\invmenu\PlayerInventoryListener;
use staffutils\StaffUtils;

final class InvSeeCommand extends Command {

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

        if (!$this->testPermission($sender)) return;

        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' <player>');

            return;
        }

        if (($target = Server::getInstance()->getPlayerByPrefix($args[0])) === null) {
            $sender->sendMessage(StaffUtils::replacePlaceholders('PLAYER_NOT_FOUND', $args[0]));

            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST)->setName($target->getName() . '\'s inventory');

        $target->getArmorInventory()->getListeners()->add($armorListener = new PlayerArmorInventoryListener($menu->getInventory()));
        $target->getInventory()->getListeners()->add($inventoryListener = new PlayerInventoryListener($menu->getInventory()));

        $contents = $target->getInventory()->getContents();
        foreach ($target->getArmorInventory()->getContents() as $slot => $item) {
            $contents[StaffUtils::ARMOR_TO_MENU_SLOTS[$slot]] = $item;
        }

        $menu->getInventory()->setContents($contents);

        $menu->getInventory()->getListeners()->add(new MenuInventoryListener(
            [MenuInventoryListener::ARMOR_INDEX => $target->getArmorInventory(), MenuInventoryListener::INVENTORY_INDEX => $target->getInventory()],
            [MenuInventoryListener::ARMOR_INDEX => $armorListener, MenuInventoryListener::INVENTORY_INDEX => $inventoryListener]
        ));

        $menu->send($sender);

        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use($inventoryListener, $armorListener, $target): void {
            $target->getArmorInventory()->getListeners()->remove($armorListener);
            $target->getInventory()->getListeners()->remove($inventoryListener);
        });
    }
}