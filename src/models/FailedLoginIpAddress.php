<?php
namespace Sil\SilAuth\models;

use Psr\Log\LoggerAwareInterface;
use Sil\SilAuth\behaviors\CreatedAtUtcBehavior;
use Sil\SilAuth\http\Request;
use Sil\SilAuth\time\UtcTime;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use Yii;

class FailedLoginIpAddress extends FailedLoginIpAddressBase implements LoggerAwareInterface
{
    use \Sil\SilAuth\traits\LoggerAwareTrait;
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'ip_address' => Yii::t('app', 'IP Address'),
            'occurred_at_utc' => Yii::t('app', 'Occurred At (UTC)'),
        ]);
    }
    
    public function behaviors()
    {
        return [
            [
                'class' => CreatedAtUtcBehavior::className(),
                'attributes' => [
                    Model::EVENT_BEFORE_VALIDATE => 'occurred_at_utc',
                ],
            ],
        ];
    }
    
    public static function countRecentFailedLoginsFor($ipAddress)
    {
        return self::find()->where([
            'ip_address' => $ipAddress,
        ])->andWhere([
            '>=', 'occurred_at_utc', UtcTime::format('-60 minutes')
        ])->count();
    }
    
    public static function getFailedLoginsFor($ipAddress)
    {
        if ( ! Request::isValidIpAddress($ipAddress)) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a valid IP address.',
                var_export($ipAddress, true)
            ));
        }
        
        return self::findAll(['ip_address' => $ipAddress]);
    }
    
    /**
     * Get the most recent failed-login record for the given IP address, or null
     * if none is found.
     *
     * @param string $ipAddress The IP address.
     * @return FailedLoginIpAddress|null
     */
    public static function getMostRecentFailedLoginFor($ipAddress)
    {
        return self::find()->where([
            'ip_address' => $ipAddress,
        ])->orderBy([
            'occurred_at_utc' => SORT_DESC,
        ])->one();
    }
    
    public function init()
    {
        $this->initializeLogger();
        parent::init();
    }
    
    public static function isCaptchaRequiredFor($ipAddress)
    {
        throw new \Exception(__CLASS__ . '.' . __FUNCTION__ . ' not yet implemented.');
    }
}
