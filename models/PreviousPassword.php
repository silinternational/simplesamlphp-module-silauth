<?php
namespace SilAuth\models;

use Illuminate\Database\Eloquent\Model;

class PreviousPassword extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'previous_password';
    
    /**
     * Get the User record that this PreviousPassword belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
