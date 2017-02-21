<?php
namespace Sil\SilAuth\models;

use Psr\Log\LoggerAwareInterface;
use Sil\SilAuth\behaviors\CreatedAtUtcBehavior;
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
