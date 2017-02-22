<?php
namespace Sil\SilAuth\models;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\behaviors\CreatedAtUtcBehavior;
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
     * Get the number of seconds remaining until the specified username is
     * no longer blocked by a rate-limit. Returns zero if the user is not
     * currently blocked.
     * 
     * @param string $username The username in question
     * @return int The number of seconds
     */
    public static function getSecondsUntilUnblocked($username)
    {
        $failedLogin = self::getMostRecentFailedLoginFor($username);
        
        return Authenticator::getSecondsUntilUnblocked(
            self::countRecentFailedLoginsFor($username),
            $failedLogin->occurred_at_utc ?? null
        );
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
        return Authenticator::isEnoughFailedLoginsToRequireCaptcha(
            self::countRecentFailedLoginsFor($username)
        );
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
    
    public static function resetFailedLoginsBy($username)
    {
        self::deleteAll(['username' => $username]);
    }
}
