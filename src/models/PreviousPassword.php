<?php
namespace Sil\SilAuth\models;

use \yii\helpers\ArrayHelper;
use Yii;

class PreviousPassword extends PreviousPasswordBase
{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'created_utc' => Yii::t('app', 'Created (UTC)'),
        ]);
    }
    
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'created_utc',
                'default',
                'value' => gmdate(User::TIME_FORMAT),
            ],
        ], parent::rules());
    }
}
