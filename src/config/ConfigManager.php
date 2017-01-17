<?php
namespace Sil\SilAuth\config;

use Sil\SilAuth\text\Text;

class ConfigManager
{
    const SEPARATOR = '.';
    
    public static function getSspConfig()
    {
        return require __DIR__ . '/ssp-config.php';
    }
    
    public static function getSspConfigFor($category)
    {
        return self::getConfigFor($category, self::getSspConfig());
    }
    
    public static function getConfigFor($category, $config)
    {
        $categoryPrefix = $category . self::SEPARATOR;
        $categoryConfig = [];
        foreach ($config as $key => $value) {
            if (Text::startsWith($key, $categoryPrefix)) {
                $subKey = self::removeCategory($key);
                $categoryConfig[$subKey] = $value;
            }
        }
        return $categoryConfig;
    }
    
    public static function getYii2Config()
    {
        return require __DIR__ . '/yii2-config.php';
    }
    
    public static function removeCategory($key)
    {
        if ($key === null) {
            return null;
        }
        $pieces = explode(self::SEPARATOR, $key, 2);
        return last($pieces);
    }
}
