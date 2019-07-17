<?php

namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class UserThrottle extends Model
{
    /**
     * Status constants
     */
    const STATUS_ALLOWED = 'allowed';
    const STATUS_BLOCKED = 'blocked';

    /**
     * Database table
     * @var string
     */
    protected $tableName = 'user_throttles';

    /**
     * Default timeout time
     * @var string
     */
    protected $timeout = '-1 minute';

    /**
     * Default number of attempts before blocking
     * @var integer
     */
    protected $allowedAttempts = 5;


    /**
     * Check the timeout to see if it has passed
     *
     * @param string $timeout Alternative timeout string (ex: "-1 minute")
     * @return boolean True if user is reendabled, false if still disabled
     */
    public function checkTimeout( $timeout = null )
    {
        $timeout = ($timeout === null) ? $this->timeout : $timeout;

        $lastChange = $this->status_change;
        $timeout = new \DateTime($timeout);

        if ($lastChange <= $timeout) {
            return $this->allow();
        }
        return false;
    }

    /**
     * Mark a user as allowed (status change)
     *
     * @return boolean Success/fail of save operation
     */
    public function allow()
    {
        $this->attempts = 1;
        $this->status_change = new \DateTime();
        $this->status = static::STATUS_ALLOWED;
        return $this->save();
    }

    /**
     * Mark a user as blocked (status change)
     *
     * @return boolean Success/fail of save operation
     */
    public function block()
    {
        $this->status_change = new \DateTime();
        $this->status = static::STATUS_BLOCKED;
        return $this->save();
    }

    /**
     * Check if throttle is blocked
     * @return bool
     */
    public function isBlocked()
    {
        return ($this->status === static::STATUS_BLOCKED) ? true : false;
    }

    /**
     * Check the number of attempts to see if it meets the threshold
     *
     * @return boolean False if they were blocked, true otherwise
     */
    public function checkAttempts()
    {
        if ($this->attempts == $this->allowedAttempts) {
            return $this->block();
        } else {
            $this->updateAttempts();
        }
        return true;
    }

    /**
     * Update the number of attempts for the current record
     *
     * @return boolean Success/fail of save operation
     */
    public function updateAttempts()
    {
        if ($this->getKey() != null) {
            $this->last_attempt = new \DateTime();
            $this->attempts = $this->attempts + 1;
            return $this->save();
        }
    }

    /**
     * Get User Throttle
     * @param int $userId
     * @return static
     */
    public static function findByUser( $userId )
    {
        $throttle = static::where('user_id', $userId)->first();
        if ($throttle === null) {
            $throttle = new static();
            $throttle->save([
                'id' => null,
                'user_id' => $userId,
                'attempts' => 1,
                'status' => static::STATUS_ALLOWED,
                'last_attempt' => new \DateTime(),
                'status_change' => new \DateTime()
            ]);
        }
        return $throttle;

    }

    /**
     * Delete User throttle
     * @param int $userId
     * @return bool
     */
    public static function destroyByUser($userId)
    {
        return static::destroy('user_id', $userId);
    }


}