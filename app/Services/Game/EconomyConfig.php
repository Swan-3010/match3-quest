<?php
namespace App\Services\Game;

use App\Models\GameConfig;

class EconomyConfig
{
    public static function get(string $key, $default = null)
    {
        $config = GameConfig::where('config_key', 'economy')->first();
        if ($config && is_array($config->config_value) && array_key_exists($key, $config->config_value)) {
            return $config->config_value[$key];
        }
        return config("game.economy.$key", $default);
    }
}
