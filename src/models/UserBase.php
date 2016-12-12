<?php

namespace Sil\SilAuth\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $uuid
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $active
 * @property string $locked
 * @property integer $login_attempts
 * @property string $block_until
 * @property string $created_at
 * @property string $updated_at
 *
 * @property PreviousPassword[] $previousPasswords
 */
class UserBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'employee_id', 'first_name', 'last_name', 'username', 'email', 'login_attempts'], 'required'],
            [['active', 'locked'], 'string'],
            [['login_attempts'], 'integer'],
            [['block_until', 'created_at', 'updated_at'], 'safe'],
            [['uuid', 'employee_id', 'first_name', 'last_name', 'username', 'email', 'password_hash'], 'string', 'max' => 255],
            [['uuid'], 'unique'],
            [['employee_id'], 'unique'],
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uuid' => Yii::t('app', 'Uuid'),
            'employee_id' => Yii::t('app', 'Employee ID'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'active' => Yii::t('app', 'Active'),
            'locked' => Yii::t('app', 'Locked'),
            'login_attempts' => Yii::t('app', 'Login Attempts'),
            'block_until' => Yii::t('app', 'Block Until'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPreviousPasswords()
    {
        return $this->hasMany(PreviousPassword::className(), ['user_id' => 'id']);
    }
}
