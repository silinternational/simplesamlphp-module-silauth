<?php
namespace Sil\SilAuth\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class User extends ActiveRecord
{
//    protected $fillable = [
//        'uuid',
//        'employee_id',
//        'first_name',
//        'last_name',
//        'username',
//        'email',
//    ];
    
    public function rules()
    {
        return ArrayHelper::merge([
            [
                [
                    'uuid',
//                    'employee_id',
//                    'first_name',
//                    'last_name',
//                    'username',
//                    'email',
//                    'active',
//                    'locked',
//                    'login_attempts',
//                    'created_at',
//                    'updated_at',
                ],
                'required',
            ],
            ['email', 'email'],
        ], parent::rules());
    }
    
    /**
     * @return string The name of the table associated with this ActiveRecord
     *     class.
     */
    public static function tableName()
    {
        return 'user';
    }
    
//    /**
//     * Get the PreviousPassword records for this User.
//     */
//    public function previousPasswords()
//    {
//        return $this->hasMany(PreviousPassword::class);
//    }
}
