<?php
namespace Sil\SilAuth\models;

class PreviousPassword extends PreviousPasswordBase
{
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'created_utc',
                'default',
                'value' => gmdate('Y-m-d H:i:s'),
            ],
        ], parent::rules());
    }
}
