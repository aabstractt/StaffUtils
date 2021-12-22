<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use staffutils\command\BanCommand;
use staffutils\command\UnbanCommand;
use staffutils\listener\PlayerPreLoginListener;
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

        $this->registerListener(new PlayerPreLoginListener());

        $this->unregisterCommand('ban');
        $this->registerCommand(
            new BanCommand('ban', 'Ban command', '', ['ipban']),
            new UnbanCommand('unban')
        );
    }

    /**
     * @param Command ...$commands
     */
    private function registerCommand(Command... $commands): void {
        $this->getServer()->getCommandMap()->registerAll('staffutils', $commands);
    }

    /**
     * @param Listener ...$listeners
     */
    private function registerListener(Listener... $listeners): void {
        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }
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
        if (($timeRemaining = self::timeRemaining($timeArgument)) === null) {
            return null;
        }

        return is_int($value = strtotime('+ ' . $timeRemaining)) ? $value : null;
    }

    /**
     * @param string $timeString
     *
     * @return string|null
     */
    public static function timeRemaining(string $timeString): ?string {
        $characters = preg_replace('/[0-9]+/', '', $timeString);

        $match = match ($characters) {
            's' => 'second',
            'm' => 'minute',
            'h' => 'hour',
            'd' => 'day',
            default => null
        };

        $int = (int) preg_replace('/[a-z]+/', '', $timeString);

        if ($match === null || $int < 1) {
            return null;
        }

        if ($int > 1) $match .= 's';

        return $int . ' ' . $match;
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