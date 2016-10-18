<?php
namespace App\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Common\Helper;

final class User extends BaseModel
{
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'email',
        'role_id',
        'status'
    ];

    protected $hidden = [
        'password',
        'access_token',
        'password_reset_token',
    ];

    public static $schemaName = 'App\Schema\UserSchema';

    public static $expand = [
        'role' => 'App\Model\Role',
        'role.rights' => 'App\Model\Right',
    ];

    public static $rules = [
        'create' => [
            'email' => 'required|email',
            'role_id' => 'required',
            'password' => 'required',
        ],
        'update' => [
            'email' => 'required|email',
            'role_id' => 'required',
        ]
    ];

    public function role()
    {
        return $this->hasOne('App\Model\Role', 'id', 'role_id');
    }

    /**
     * @param $email
     *
     * @return bool
     */
    public static function exist($email)
    {
        return User::where('email', $email)->count() > 0;
    }

    public static function findUserByEmail($email)
    {
        return User::where('email', $email)->where('status', 1)->first();
    }

    public static function findUserByAccessToken($accessToken)
    {
        return User::where('access_token', md5($accessToken))->where('status', 1)->first();
    }

    public static function findByPasswordResetToken($resetToken)
    {
        if (!User::isPasswordResetTokenValid($resetToken)) {
            return null;
        }

        return User::where('password_reset_token', $resetToken)->where('status', 1)->first();
    }

    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        // TODO Вынести expire в конфиг
        $expire = 3600;
        return $timestamp + $expire >= time();
    }

    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Helper::generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function setPassword($password)
    {
        $this->access_token = null;
        $this->password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 13]);
    }
}