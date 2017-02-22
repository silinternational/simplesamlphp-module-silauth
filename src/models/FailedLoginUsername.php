<?php
namespace Sil\SilAuth\models;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Sil\SilAuth\time\UtcTime;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use Yii;

class FailedLoginUsername extends FailedLoginUsernameBase implements LoggerAwareInterface
{
    use \Sil\SilAuth\traits\LoggerAwareTrait;
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
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
    
    public static function countRecentFailedLoginsFor($username)
    {
        return self::find()->where([
            'username' => $username,
        ])->andWhere([
            '>=', 'occurred_at_utc', UtcTime::format('-60 minutes')
        ])->count();
    }
    
    /**
     * Find the records with the given username (if any).
     * 
     * @param string $username The username.
     * @return FailedLoginUsername[] An array of any matching records.
     */
    public static function getFailedLoginsFor($username)
    {
        return self::findAll(['username' => $username]);
    }
    
    /**
     * Get the number of seconds remaining until the block_until_utc datetime is
     * reached. Returns zero if the user is not blocked.
     * 
     * @return int
     */
    public function getSecondsUntilUnblocked()
    {
        if ($this->block_until_utc === null) {
            return 0;
        }
        
        $nowUtc = new UtcTime();
        $blockUntilUtc = new UtcTime($this->block_until_utc);
        $remainingSeconds = $nowUtc->getSecondsUntil($blockUntilUtc);
        
        return max($remainingSeconds, 0);
    }
    
    /**
     * Get a human-friendly wait time.
     *
     * @return WaitTime
     */
    public function getWaitTimeUntilUnblocked()
    {
        $secondsUntilUnblocked = $this->getSecondsUntilUnblocked();
        return new WaitTime($secondsUntilUnblocked);
    }
    
    public function init()
    {
        $this->initializeLogger();
        parent::init();
    }
    
    /**
     * Find out whether a rate limit is blocking the specified username.
     *
     * @param string $username The username
     * @return bool
     */
    public static function isRateLimitBlocking($username)
    {
        return Authenticator::isEnoughFailedLoginsToBlock(
            self::countRecentFailedLoginsFor($username)
        );
    }
    
    public static function isCaptchaRequiredFor($username)
    {
        throw new \Exception(__CLASS__ . '.' . __FUNCTION__ . ' not yet implemented.');
        
        //return ($this->login_attempts >= Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN);
    }
    
    public static function recordFailedLoginBy(
        $username,
        LoggerInterface $logger
    ) {
        $newRecord = new FailedLoginUsername(['username' => $username]);
        if ( ! $newRecord->save()) {
            $logger->critical(sprintf(
                'Failed to update login attempts counter in database for %s, '
                . 'so unable to prevent dictionary attacks by that username. '
                . 'Errors: %s',
                var_export($username, true),
                json_encode($newRecord->getErrors())
            ));
        }
    }
}
