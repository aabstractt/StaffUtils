<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\utils\EnumTrait;

/**
 * @method static StaffResult ALREADY_BANNED()
 * @method static StaffResult SUCCESS_BANNED()
 * @method static StaffResult UNBAN_FAIL()
 * @method static StaffResult SUCCESS_UNBANNED()
 *
 * @method static StaffResult ALREADY_MUTED()
 * @method static StaffResult SUCCESS_MUTED()
 * @method static StaffResult UNMUTE_FAIL()
 * @method static StaffResult SUCCESS_UNMUTED()
 *
 * @method static StaffResult ALREADY_WARNED()
 * @method static StaffResult SUCCESS_WARNED()
 */
class StaffResult {

    use EnumTrait;

    /**
     * @param string $enumName
     *
     * @return StaffResult
     */
    public static function valueOf(string $enumName): StaffResult {
        return self::getAll()[$enumName];
    }

    /**
     * Inserts default entries into the registry.
     *
     * (This ought to be private, but traits suck too much for that.)
     */
    protected static function setup(): void {
        self::registerAll(
            new self('already_banned'),
            new self('success_banned'),
            new self('already_muted'),
            new self('success_muted'),
            new self('unban_fail'),
            new self('success_unbanned'),
            new self('unmute_fail'),
            new self('success_unmuted')
        );
    }
}