<?php
namespace Sil\SilAuth\models;

use Sil\SilAuth\time\UtcTime;
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
                'value' => UtcTime::format(),
            ],
        ], parent::rules());
    }
}
