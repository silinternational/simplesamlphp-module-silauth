<?php
namespace Sil\SilAuth\models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'uuid',
        'employee_id',
        'first_name',
        'last_name',
        'username',
        'email',
    ];
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
    
    /**
     * Get the PreviousPassword records for this User.
     */
    public function previousPasswords()
    {
        return $this->hasMany(PreviousPassword::class);
    }
}
