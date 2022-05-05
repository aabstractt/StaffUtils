<?php

declare(strict_types=1);

namespace staffutils\listener\invmenu;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

final class PlayerInventoryListener implements InventoryListener {

    /**
     * @param Inventory $inventory
     */
    public function __construct(
        private Inventory $inventory
    ) {}

    public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void {
        $this->inventory->setItem($slot, $inventory->getItem($slot));
    }

    /**
     * @param Item[] $oldContents
     */
    public function onContentChange(Inventory $inventory, array $oldContents): void {
        $this->inventory->setContents($inventory->getContents());
    }
}