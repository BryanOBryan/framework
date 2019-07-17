<?php

namespace Kaliba\Robas\Models;
use Kaliba\ORM\Model;

class PasswordReset extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'password_resets';

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Save the new reset code to the data source
     *
     * @param int $userId User Id
     * @param string $resetCode Reset code
     * @param \DateTime $timeout Expire time
     * @return boolean  Success/fail of token creation
     */
    public function saveResetCode($userId, $resetCode, $timeout)
    {
        return $this->save([
            'user_id' => $userId,
            'reset_code' => $resetCode,
            'reset_timeout' => $timeout
        ]);
    }

    /**
     * Find the current reset code
     *
     * @param string $resetCode Reset code
     * @return static
     */
    public static function findResetCode($resetCode)
    {
        return static::where('reset_code', $resetCode)->first();
    }


}