<?php


namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class UserGroup extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'user_groups';

    /**
     * Add User Group
     * @param int $userId
     * @param int $groupId
     * @return bool
     */
    public function add( $userId, $groupId)
    {
        try {
            return $this->save([
                'user_id' => $userId,
                'group_id' => $groupId
            ]);
        } catch (\PDOException $ex) {}

    }

    /**
     * Remove User Group
     * @param int $userId
     * @param int $groupId
     * @return bool
     */
    public static function remove( $userId, $groupId )
    {
        try {
            return static::destroy([
                'user_id' => $userId,
                'group_id' => $groupId,
            ]);
        } catch (\PDOException $ex) {}

    }

    /**
     * Get User Group By UserID and GroupID
     * @param int $userId
     * @param int $groupId
     * @return static
     */
    public static function get( $userId, $groupId )
    {
        return static::where([
            'user_id' => $userId,
            'group_id' => $groupId,
        ])->first();
    }


}