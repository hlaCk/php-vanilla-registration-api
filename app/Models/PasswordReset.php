<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;

/**
 * Class Session
 *
 * @property-read  int    $id
 * @property       string $ip_address
 * @property       string $last_activity
 * @property       string $payload
 * @property       string $user_agent
 * @property-read  int    $user_id
 *
 */
class PasswordReset extends AbstractModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'password_resets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email',
        'token',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByEmail(Builder $builder, $email)
    {
        return $builder->whereIn('email', array_wrap($email));
    }
    
    public function scopeByUser(Builder $builder, $user = null)
    {
        $user ??= currentUser();
        $user = is_numeric($user) ? User::find($user) : $user;
        return $builder->byEmail(array_filter([ $user->email, $user->email2 ]));
    }

    public function scopeByToken(Builder $builder, $token = null)
    {
        $token ??= request('token');
        return $builder->whereIn('token', array_wrap($token));
    }
}
