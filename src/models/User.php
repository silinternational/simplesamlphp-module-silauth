<?php
namespace Sil\SilAuth\models;

use Ramsey\Uuid\Uuid;
use Sil\SilAuth\UtcTime;
use yii\helpers\ArrayHelper;
use Yii;

class User extends UserBase
{
    const ACTIVE_NO = 'No';
    const ACTIVE_YES = 'Yes';
    
    const BLOCK_AFTER_NTH_FAILED_LOGIN = 2;
    
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
        
        $blockForInterval = new \DateInterval(sprintf(
            'PT%sS', // = P(eriod)T(ime)#S(econds)
            ($failedLoginAttempts * $failedLoginAttempts)
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
    
    public function isActive()
    {
        return (strcasecmp($this->active, self::ACTIVE_YES) === 0);
    }
    
    public function isBlockedByRateLimit()
    {
        if ($this->block_until_utc === null) {
            return false;
        }
        
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $blockUntilUtc = new \DateTime($this->block_until_utc, new \DateTimeZone('UTC'));
        
        return ($blockUntilUtc > $nowUtc);
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
            Yii::error(sprintf(
                'Failed to update login attempts counter in database for %s.',
                $this->username
            ));
        }
    }
    
    public function resetFailedLoginAttemptsInDatabase()
    {
        $this->login_attempts = 0;
        $successful = $this->save(true, ['login_attempts', 'block_until_utc']);
        if ( ! $successful) {
            Yii::error(sprintf(
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
