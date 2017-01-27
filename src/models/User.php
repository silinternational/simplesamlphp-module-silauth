<?php
namespace Sil\SilAuth\models;

use Ramsey\Uuid\Uuid;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\time\UtcTime;
use Sil\SilAuth\time\WaitTime;
use yii\helpers\ArrayHelper;
use Yii;
use yii\behaviors\TimestampBehavior;

class User extends UserBase implements \Psr\Log\LoggerAwareInterface
{
    const ACTIVE_NO = 'No';
    const ACTIVE_YES = 'Yes';
    
    const LOCKED_NO = 'No';
    const LOCKED_YES = 'Yes';
    
    private $logger;
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'uuid' => Yii::t('app', 'UUID'),
            'block_until_utc' => Yii::t('app', 'Block Until (UTC)'),
            'last_updated_utc' => Yii::t('app', 'Last Updated (UTC)'),
        ]);
    }
    
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    \yii\base\Model::EVENT_BEFORE_VALIDATE => 'last_updated_utc',
                ],
                'createdAtAttribute' => false,
                'updatedAtAttribute' => 'last_updated_utc',
                'value' => function() { return UtcTime::format(); },
            ],
        ];
    }
    
    public static function calculateBlockUntilUtc($failedLoginAttempts)
    {
        if ( ! Authenticator::isEnoughFailedLoginsToBlock($failedLoginAttempts)) {
            return null;
        }
        
        $secondsToDelay = Authenticator::calculateSecondsToDelay(
            $failedLoginAttempts
        );
        
        $blockForInterval = new \DateInterval(sprintf(
            'PT%sS', // = P(eriod)T(ime)#S(econds)
            $secondsToDelay
        ));
        
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        /* @var $blockUntilUtc \DateTime */
        $blockUntilUtc = $nowUtc->add($blockForInterval);
        return $blockUntilUtc->format(UtcTime::DATE_TIME_FORMAT);
    }
    
    /**
     * Find the User record with the given username (if any).
     * 
     * @param string $username The username.
     * @return User|null The matching User record, or null if not found.
     */
    public static function findByUsername($username)
    {
        return User::findOne(['username' => $username]);
    }
    
    /**
     * Generate a UUID.
     *
     * @return string
     */
    public static function generateUuid()
    {
        return Uuid::uuid4()->toString();
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
     * Whether this User database record has a (hashed) password.
     * 
     * @return bool
     */
    public function hasPasswordInDatabase()
    {
        return ($this->password_hash !== null);
    }
    
    public function isActive()
    {
        return (strcasecmp($this->active, self::ACTIVE_YES) === 0);
    }
    
    public function isBlockedByRateLimit()
    {
        return ($this->getSecondsUntilUnblocked() > 0);
    }
    
    /**
     * Check the given password against the current password hash.
     *
     * @param string $password
     * @return bool
     */
    public function isPasswordCorrect($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    public function isLocked()
    {
        return (strcasecmp($this->locked, self::LOCKED_NO) !== 0);
    }
    
    public function isCaptchaRequired()
    {
        return ($this->login_attempts >= Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN);
    }
    
    public static function isCaptchaRequiredFor($username)
    {
        $user = self::findByUsername($username);
        if ($user === null) {
            return false;
        }
        return $user->isCaptchaRequired();
    }
    
    public function recordLoginAttemptInDatabase()
    {
        $this->login_attempts += 1;
        $successful = $this->save(true, ['login_attempts', 'block_until_utc']);
        if ( ! $successful) {
            $this->logger->critical(sprintf(
                'Failed to update login attempts counter in database for %s, so unable to prevent dictionary attacks.',
                var_export($this->username, true)
            ));
        }
    }
    
    public function resetFailedLoginAttemptsInDatabase()
    {
        $this->login_attempts = 0;
        $successful = $this->save(true, ['login_attempts', 'block_until_utc']);
        if ( ! $successful) {
            $this->logger->error(sprintf(
                'Failed to reset login attempts counter in database for %s.',
                $this->username
            ));
        }
    }
    
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'uuid',
                'default',
                'value' => function () {
                    return self::generateUuid();
                },
                'when' => function($model) {
                    return $model->isNewRecord;
                },
            ], [
                'uuid',
                'validateValueDidNotChange',
                'when' => function($model) {
                    return ! $model->isNewRecord;
                },
            ], [
                'email',
                'email',
            ], [
                'login_attempts',
                'filter',
                'filter' => function ($loginAttempts) {
                    $this->block_until_utc = User::calculateBlockUntilUtc($loginAttempts);
                    return $loginAttempts ?? 0;
                },
            ],
        ], parent::rules());
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
    
    public function setPassword($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Try to save this user. If unsuccessful, log an error to the current
     * logger (if set), prefixing it with the given error message prefix (but do
     * not throw an exception or do anything else to disrupt program flow).
     *
     * @param string $errorPrefix The first part of the error message.
     */
    public function tryToSave($errorPrefix)
    {
        $saveFailed = !$this->save();
        $loggerIsAvailable = !empty($this->logger);
        
        if ($saveFailed && $loggerIsAvailable) {
            $this->logger->critical('{errorPrefix}: {userErrors}', [
                'errorPrefix' => $errorPrefix,
                'userErrors' => print_r($this->getErrors(), true),
            ]);
        }
    }
    
    /**
     * @param string $attribute the attribute currently being validated
     */
    public function validateValueDidNotChange($attribute)
    {
        $previousUserRecord = User::findOne(['id' => $this->id]);
        if ($this->$attribute !== $previousUserRecord->$attribute) {
            $this->addError($attribute, sprintf(
                'The %s value may not change.',
                $attribute
            ));
        }
    }
}
