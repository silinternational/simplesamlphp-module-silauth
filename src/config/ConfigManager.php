<?php
namespace Sil\SilAuth\config;

use function Stringy\create as s;

class ConfigManager
{
    public static function getSspConfig()
    {
        return require __DIR__ . '/ssp-config.php';
    }
    
    public static function getSspConfigFor($category)
    {
        $categoryPrefix = $category . '.';
        $categoryConfig = [];
        foreach (self::getSspConfig() as $key => $value) {
            if (s($key)->startsWith($categoryPrefix)) {
                $subKey = s($key)->removeLeft($categoryPrefix);
                $categoryConfig[(string)$subKey] = $value;
            }
        }
        return $categoryConfig;
    }
}
