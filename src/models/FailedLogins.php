<?php
namespace Sil\SilAuth\models;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Sil\SilAuth\time\UtcTime;
use yii\helpers\ArrayHelper;
use Yii;
use yii\behaviors\TimestampBehavior;

class FailedLogins extends FailedLoginsBase implements LoggerAwareInterface
{
    private $logger;
    
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
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    \yii\base\Model::EVENT_BEFORE_VALIDATE => 'occurred_at_utc',
                ],
                'createdAtAttribute' => 'occurred_at_utc',
                'updatedAtAttribute' => null,
                'value' => function() { return UtcTime::format(); },
            ],
        ];
    }
    
    public function init()
    {
        if (empty($this->logger)) {
            $this->logger = new NullLogger();
        }
        parent::init();
    }
    
    public static function isCaptchaRequiredForIpAddress($ipAddress)
    {
        throw new \Exception(__FUNCTION__ . ' not yet implemented.');
    }
    
    public static function isCaptchaRequiredForUsername($username)
    {
        throw new \Exception(__FUNCTION__ . ' not yet implemented.');
    }
    
    /**
     * Set a logger for this User instance to use.
     *
     * @param \Psr\Log\LoggerInterface $logger A PSR-3 compliant logger.
     * @return null
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
