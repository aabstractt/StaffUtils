<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class StaffUtils extends PluginBase {

    use SingletonTrait;

    public function onEnable(): void {
        self::setInstance($this);
    }

    public static function replacePlaceholders(string $text, string... $args): string {
        return $text;
    }
}