<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use staffutils\async\LoadMysqlAsync;
use staffutils\command\AltsCommand;
use staffutils\command\BanCommand;
use staffutils\command\FreezeCommand;
use staffutils\command\KickCommand;
use staffutils\command\MuteCommand;
use staffutils\command\UnbanCommand;
use staffutils\command\UnmuteCommand;
use staffutils\command\VanishCommand;
use staffutils\command\WarnCommand;
use staffutils\listener\PlayerChatListener;
use staffutils\listener\PlayerJoinListener;
use staffutils\listener\PlayerMoveListener;
use staffutils\listener\PlayerPreLoginListener;
use staffutils\listener\PlayerQuitListener;
use staffutils\task\DiscordWebhookAsync;
use staffutils\task\QueryAsyncTask;
use staffutils\utils\TaskUtils;

class StaffUtils extends PluginBase {

    use SingletonTrait;
    /** @var BanEntry[] */
    public static array $results = [];

    /** @var array */
    private static array $messages = [];

    public function onEnable(): void {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->saveResource('messages.yml');
        $this->saveResource('queries.sql');

        self::$messages = (new Config($this->getDataFolder() . 'messages.yml'))->getAll();

        TaskUtils::init();
        TaskUtils::runAsync(new LoadMysqlAsync(), function (QueryAsyncTask $query): void {
            $this->getLogger()->info('mysql successfully loaded!');
        });

        $this->registerListener(
            new PlayerPreLoginListener(),
            new PlayerJoinListener(),
            new PlayerQuitListener(),
            new PlayerChatListener(),
            new PlayerMoveListener()
        );

        $this->unregisterCommand('ban', 'kick');
        $this->registerCommand(
            new BanCommand('ban', 'Ban command', '', ['ipban']),
            new UnbanCommand('unban'),
            new MuteCommand('mute', 'Mute command', '', ['ipmute']),
            new UnmuteCommand('unmute', 'Unmute command'),
            new AltsCommand('alts', 'See player alts', '', ['checkalts', 'dupeip']),
            new KickCommand('kick', 'Kick a player'),
            new WarnCommand('warn'),
            new FreezeCommand('freeze'),
            new VanishCommand('vanish')
        );
    }

    /**
     * @param Command ...$commands
     */
    private function registerCommand(Command...$commands): void {
        $this->getServer()->getCommandMap()->registerAll('staffutils', $commands);
    }

    /**
     * @param Listener ...$listeners
     */
    private function registerListener(Listener...$listeners): void {
        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }
    }

    /**
     * @param string ...$commands
     */
    private function unregisterCommand(string...$commands): void {
        foreach ($commands as $command) {
            if (($cmd = $this->getServer()->getCommandMap()->getCommand($command)) === null) {
                continue;
            }

            $this->getServer()->getCommandMap()->unregister($cmd);
        }
    }

    /**
     * @param string $message
     */
    public static function sendDiscordMessage(string $message): void {
        $config = self::getInstance()->getConfig();

        if (!self::getInstance()->getBoolean('discord.enabled')) {
            return;
        }

        Server::getInstance()->getAsyncPool()->submitTask(new DiscordWebhookAsync(strval($config->getNested('discord.webhook')), serialize([
            'username' => $config->getNested('discord.username'),
            'content' => $message
        ])));
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
        return date('Y-m-d H:i:s', ($timestamp === -1 ? time() : $timestamp));
    }

    /**
     * @param string $text
     * @param string ...$args
     *
     * @return string
     */
    public static function replacePlaceholders(string $text, string...$args): string {
        $message = self::$messages[$text] ?? $text;

        foreach ($args as $i => $arg) {
            $message = str_replace('{%' . $i . '}', $arg, $message);
        }

        return TextFormat::colorize($message);
    }

    /**
     * @return string
     */
    public static function daysAsString(): string {
        return is_string($value = self::getInstance()->getConfig()->getNested('durations.days', 'days')) ? $value : throw new PluginException('Invalid day value');
    }
    /**
     * @return string
     */
    public static function hoursAsString(): string {
        return is_string($value = self::getInstance()->getConfig()->getNested('durations.hours', 'hour')) ? $value : throw new PluginException('Invalid hour value');
    }

    /**
     * @return string
     */
    public static function minutesAsString(): string {
        return is_string($value = self::getInstance()->getConfig()->getNested('durations.minutes', 'minute')) ? $value : throw new PluginException('Invalid minute value');
    }

    /**
     * @return string
     */
    public static function secondsAsString(): string {
        return is_string($value = self::getInstance()->getConfig()->getNested('durations.seconds', 'second')) ? $value : throw new PluginException('Invalid second value');
    }

    /**
     * @param string $k
     * @param bool   $default
     *
     * @return bool
     */
    public function getBoolean(string $k, bool $default = false): bool {
        return is_bool($value = $this->getConfig()->getNested($k, $default)) ? $value : $default;
    }

    /**
     * @param string $string
     * @param int    $value
     *
     * @return string
     */
    public static function pluralize(string $string, int $value): string {
        return $value . ' ' . $string . ($value > 1 ? 's' : '');
    }
}