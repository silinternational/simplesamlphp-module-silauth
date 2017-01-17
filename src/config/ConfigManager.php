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
    
    public static function getMergedYii2Config($customConfig)
    {
        $defaultConfig = require __DIR__ . '/yii2-config.php';
        return array_replace_recursive(
            $defaultConfig,
            $customConfig
        );
    }
    
    private static function initializeYiiClass()
    {
        if ( ! class_exists('Yii')) {
            require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
        }
    }
    
    public static function getYii2ConsoleApp($customConfig)
    {
        self::initializeYiiClass();
        $mergedYii2Config = self::getMergedYii2Config($customConfig);
        return new \yii\console\Application($mergedYii2Config);
    }
    
    public static function initializeYii2WebApp($customConfig)
    {
        self::initializeYiiClass();
        
        /* Initialize the Yii web application. Note that we do NOT call run()
         * here, since we don't want Yii to handle the HTTP request. We just
         * want the Yii classes available for use (including database
         * models).  */
        new \yii\web\Application(self::getMergedYii2Config($customConfig));
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
