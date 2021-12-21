<?php

declare(strict_types=1);

namespace staffutils;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use Predis\Client;

class StaffUtils extends PluginBase {

    use SingletonTrait;
    /** @var array */
    private static array $messages = [];

    public function onEnable(): void {
        $bootstrap = 'phar://' . $this->getServer()->getPluginPath() . 'StaffUtils.phar/vendor/autoload.php';
        if(!is_file($bootstrap)){
            echo "[ERROR] Composer autoloader not found at " . $bootstrap . PHP_EOL;
            echo "[ERROR] Please install/update Composer dependencies or use provided builds." . PHP_EOL;

            exit(1);
        }

        require_once($bootstrap);

        echo 'Loading' . PHP_EOL;

        self::setInstance($this);

        $this->init();

        $this->saveDefaultConfig();
        $this->saveResource('messages.yml');

        self::$messages = (new Config($this->getDataFolder() . 'messages.yml'))->getAll();
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
            $message = str_replace('{%' . $i . '}', $args, $message);
        }

        return $message;
    }

    private function init(): void {
        $thread = new PubSubThread('phar://' . $this->getServer()->getPluginPath() . 'StaffUtils.phar/vendor/autoload.php');

        $thread->start();

        echo 'iniciando thread' . PHP_EOL;
    }
}