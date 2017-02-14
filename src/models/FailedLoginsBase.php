<?php

namespace Sil\SilAuth\models;

use Yii;

/**
 * This is the model class for table "failed_logins".
 *
 * @property integer $id
 * @property string $username
 * @property string $ip_address
 * @property string $occurred_at_utc
 */
class FailedLoginsBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'failed_logins';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'ip_address', 'occurred_at_utc'], 'required'],
            [['occurred_at_utc'], 'safe'],
            [['username'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'occurred_at_utc' => Yii::t('app', 'Occurred At Utc'),
        ];
    }
}
