<?php

declare(strict_types=1);

namespace staffutils\task;

use pocketmine\plugin\PluginException;
use pocketmine\scheduler\AsyncTask;

class DiscordWebhookAsync extends AsyncTask {

    /**
     * @param string $webhook
     * @param string $contents
     */
    public function __construct(
        private string $webhook,
        private string $contents
    ) {}

    /**
     * Actions to execute when run
     */
    public function onRun(): void {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->webhook);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(unserialize($this->contents)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($curl);
    }
}