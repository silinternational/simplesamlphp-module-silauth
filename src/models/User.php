<?php
namespace Sil\SilAuth\models;

use Ramsey\Uuid\Uuid;
use yii\helpers\ArrayHelper;
use Yii;

class User extends UserBase
{
    const MAX_FAILED_LOGINS_BEFORE_BLOCK = 2;
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
    
    public function isBlockedByRateLimit()
    {
        if ($this->block_until_utc === null) {
            return false;
        }
        
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $blockUntilUtc = new \DateTime($this->block_until_utc, new \DateTimeZone('UTC'));
        
        return ($blockUntilUtc > $nowUtc);
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
                'value' => gmdate('Y-m-d H:i:s'),
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
