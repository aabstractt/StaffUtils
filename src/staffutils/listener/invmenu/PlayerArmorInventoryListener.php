<?php

declare(strict_types=1);

namespace staffutils\listener\invmenu;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;
use staffutils\StaffUtils;

final class PlayerArmorInventoryListener implements InventoryListener {

    /**
     * @param Inventory $inventory
     */
    public function __construct(
        private Inventory $inventory
    ) {}

    /**
     * @param Inventory $inventory
     * @param int       $slot
     * @param Item      $oldItem
     */
    public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void {
        if (($targetSlot = StaffUtils::ARMOR_TO_MENU_SLOTS[$slot] ?? -1) === -1) {
            return;
        }

        $this->inventory->setItem($targetSlot, $inventory->getItem($slot));
    }

    /**
     * @param Item[] $oldContents
     */
    public function onContentChange(Inventory $inventory, array $oldContents): void {
        $this->inventory->setContents($inventory->getContents());
    }
}