<?php

namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class UserToken extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'user_tokens';

    /**
     * Get User
     * @return User
     */
    public function getUser()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get token string
     * @return string Token string
     */
    public function getToken()
    {
        if(!empty($this->token)){
            return $this->token;
        }
    }

    /**
     * Save the new token to the data source
     *
     * @param string $token Token string
     * @param int $userId User Id
     * @param \DateTime $expires Expire time
     * @return boolean  Success/fail of token creation
     */
    public function saveToken( $token, $userId, $expires )
    {
        return $this->save([
            'token' => $token,
            'user_id' => $userId,
            'expires' => $expires
        ]);
    }

    /**
     * Find the current token records for the provided user ID
     *
     * @param integer $userId User ID
     * @return static
     */
    public static function findByUser( $userId )
    {
        return static::where('user_id', $userId)->first();
    }

    /**
     * Get the token information searching on given token string
     *
     * @param string $tokenValue Token string for search
     * @return static Instance
     */
    public static function findByToken( $token )
    {
        return static::where('token', $token)->first();
    }

    /**
     * Delete the token by token string
     *
     * @param string $token Token hash string
     * @return boolean Success/fail of token record deletion
     */
    public static function deleteByToken( $token )
    {
        return static::destroy('token', $token);
    }


}