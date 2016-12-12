<?php
namespace Sil\SilAuth\models;

use Ramsey\Uuid\Uuid;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property string $uuid
 */
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
    
    public static function generateUuid()
    {
        return Uuid::uuid4()->toString();
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
            ], [
                'email',
                'email',
            ],
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
