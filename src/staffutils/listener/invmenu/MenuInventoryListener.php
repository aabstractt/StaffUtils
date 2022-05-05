<?php

declare(strict_types=1);

namespace staffutils\listener\invmenu;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;
use staffutils\StaffUtils;

final class MenuInventoryListener implements InventoryListener {

    public const ARMOR_INDEX = 0;
    public const INVENTORY_INDEX = 1;

    /**
     * @param Inventory[] $inventories
     * @param InventoryListener[] $inventoriesListener
     */
    public function __construct(
        private array $inventories,
        private array $inventoriesListener,
    ) {}

    public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void {
        foreach ($this->inventories as $index => $inv) {
            $inv->getListeners()->remove($this->inventoriesListener[$index]);
        }

        $this->inventories[isset(StaffUtils::MENU_TO_ARMOR_SLOTS[$slot]) ? self::ARMOR_INDEX : self::INVENTORY_INDEX]->setItem(StaffUtils::MENU_TO_ARMOR_SLOTS[$slot] ?? $slot, $inventory->getItem($slot));

        foreach ($this->inventories as $index => $inv) {
            $inv->getListeners()->add($this->inventoriesListener[$index]);
        }
    }

    /**
     * @param Item[] $oldContents
     */
    public function onContentChange(Inventory $inventory, array $oldContents): void {
        foreach ($this->inventories as $index => $inv) {
            $inv->getListeners()->remove($this->inventoriesListener[$index]);

            $inv->setContents($inventory->getContents());

            $inv->getListeners()->add($this->inventoriesListener[$index]);
        }
    }
}