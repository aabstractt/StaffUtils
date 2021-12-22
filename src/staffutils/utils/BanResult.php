<?php

declare(strict_types=1);

namespace staffutils\utils;

use pocketmine\utils\EnumTrait;

/**
 * @method static BanResult ALREADY_BANNED()
 * @method static BanResult SUCCESS()
 */
class BanResult {

    use EnumTrait;

    /**
     * Inserts default entries into the registry.
     *
     * (This ought to be private, but traits suck too much for that.)
     */
    protected static function setup(): void {
        self::registerAll(
            new self('already_banned'),
            new self('success')
        );
    }
}