<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use staffutils\command\BanCommand;
use staffutils\utils\TaskUtils;

class StaffUtils extends PluginBase {

    use SingletonTrait;
    /** @var array */
    private static array $messages = [];

    public function onEnable(): void {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->saveResource('messages.yml');

        self::$messages = (new Config($this->getDataFolder() . 'messages.yml'))->getAll();

        TaskUtils::init();

        $this->unregisterCommand('ban');
        $this->getServer()->getCommandMap()->register('pocketmine', new BanCommand('ban', 'Ban command', '', ['ipban']));
    }

    /**
     * @param string ...$commands
     */
    private function unregisterCommand(string... $commands): void {
        foreach ($commands as $command) {
            if (($cmd = $this->getServer()->getCommandMap()->getCommand($command)) === null) {
                continue;
            }

            $this->getServer()->getCommandMap()->unregister($cmd);
        }
    }

    /**
     * @param string $timeArgument
     *
     * @return int|null
     */
    public static function calculateTime(string $timeArgument): ?int {
        return is_int($value = strtotime('+ ' . self::timeRemaining($timeArgument))) ? $value : null;
    }

    /**
     * @param string $timeString
     *
     * @return string
     */
    public static function timeRemaining(string $timeString): string {
        $characters = str_replace('[0-9]', '', $timeString);

        $match = match ($characters) {
            's' => 'second',
            'h' => 'hour',
            'd' => 'day',
            default => 'minute'
        };

        $int = (int) str_replace('[a-z]', '', $timeString);

        if ($int > 1) $match .= 's';

        return $int . $match;
    }

    /**
     * @param int $timestamp
     *
     * @return string
     */
    public static function dateNow(int $timestamp = -1): string {
        return date('d/m/Y H:i:s', ($timestamp === -1 ? time() : $timestamp));
    }

    /**
     * @param string $text
     * @param string ...$args
     *
     * @return string
     */
    public static function replacePlaceholders(string $text, string... $args): string {
        $message = self::$messages[$text] ?? $text;

        foreach ($args as $i => $arg) {
            $message = str_replace('{%' . $i . '}', $arg, $message);
        }

        return TextFormat::colorize($message);
    }
}