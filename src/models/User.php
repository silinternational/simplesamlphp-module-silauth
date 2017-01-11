<?php
namespace Sil\SilAuth\models;

use Ramsey\Uuid\Uuid;
use Sil\SilAuth\auth\AuthError;
use Sil\SilAuth\time\UtcTime;
use yii\helpers\ArrayHelper;
use Yii;

class User extends UserBase
{
    const ACTIVE_NO = 'No';
    const ACTIVE_YES = 'Yes';
    
    const BLOCK_AFTER_NTH_FAILED_LOGIN = 2;
    const MAX_SECONDS_TO_BLOCK = 3600; // 3600 seconds = 1 hour
    
    const LOCKED_NO = 'No';
    const LOCKED_YES = 'Yes';
    
    const TIME_FORMAT = 'Y-m-d H:i:s';
    
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
    
    public static function calculateBlockUntilUtc($failedLoginAttempts)
    {
        if ( ! self::isEnoughFailedLoginsToBlock($failedLoginAttempts)) {
            return null;
        }
        
        $secondsToDelay = min(
            $failedLoginAttempts * $failedLoginAttempts,
            self::MAX_SECONDS_TO_BLOCK
        );
        
        $blockForInterval = new \DateInterval(sprintf(
            'PT%sS', // = P(eriod)T(ime)#S(econds)
            $secondsToDelay
        ));
        
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        /* @var $blockUntilUtc \DateTime */
        $blockUntilUtc = $nowUtc->add($blockForInterval);
        return $blockUntilUtc->format(self::TIME_FORMAT);
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
    
    public static function generateUuid()
    {
        return Uuid::uuid4()->toString();
    }
    
    public function getFriendlyWaitTimeUntilUnblocked()
    {
        $secondsUntilUnblocked = $this->getSecondsUntilUnblocked();
        return self::getFriendlyWaitTimeFor($secondsUntilUnblocked);
    }
    
    /**
     * Get a human-friendly description of approximately how long the user must
     * wait before (at least) the given number of seconds have elapsed.
     * 
     * NOTE: This will not be precise, as it may round up to have a more
     *       natural-sounding result (e.g. 'about 20 seconds' rather than
     *       '17 seconds').
     * 
     * @param int $secondsToWait The number of seconds the user must wait.
     * @return string|null A string describing the remaining wait time (e.g.
     *     '20 seconds', '3 minutes', etc.), or null if no waiting is necessary.
     */
    public static function getFriendlyWaitTimeFor($secondsToWait)
    {
        if ($secondsToWait < 1) {
            return null;
        }
        
        if ($secondsToWait <= 5) {
            $valueToUse = 5;
            $unitToUse = 'second';
        } elseif ($secondsToWait <= 30) {
            $valueToUse = (int) ceil($secondsToWait / 10) * 10;
            $unitToUse = 'second';
        } else {
            $valueToUse = (int) ceil($secondsToWait / 60);
            $unitToUse = 'minute';
        }
        
        return sprintf(
            'about %s %s%s',
            $valueToUse,
            $unitToUse,
            (($valueToUse === 1) ? '' : 's')
        );
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
    
    public function isPasswordCorrect($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    protected function isEnoughFailedLoginsToBlock($failedLoginAttempts)
    {
        return ($failedLoginAttempts >= self::BLOCK_AFTER_NTH_FAILED_LOGIN);
    }
    
    public function isLocked()
    {
        return (strcasecmp($this->locked, self::LOCKED_NO) !== 0);
    }
    
    public function recordLoginAttemptInDatabase()
    {
        $this->login_attempts += 1;
        $successful = $this->save(true, ['login_attempts', 'block_until_utc']);
        if ( ! $successful) {
            AuthError::logError(sprintf(
                'Failed to update login attempts counter in database for %s.',
                var_export($this->username, true)
            ));
        }
    }
    
    public function resetFailedLoginAttemptsInDatabase()
    {
        $this->login_attempts = 0;
        $successful = $this->save(true, ['login_attempts', 'block_until_utc']);
        if ( ! $successful) {
            AuthError::logError(sprintf(
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
                'last_updated_utc',
                'default',
                'value' => gmdate(self::TIME_FORMAT),
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
    
    public function setPassword($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
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
