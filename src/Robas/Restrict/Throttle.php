<?php

namespace Kaliba\Robas\Restrict;
use Kaliba\Robas\Restrict\Policy;
use Kaliba\Robas\Models\UserThrottle;
use Kaliba\Robas\Models\User;

class Throttle implements  Policy
{
    /**
     * @var User
     */
    private $user;

    /**
     * Throttle constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the evaluation for the restriction
     * @param mixed $data
     * @return bool
     * @throws \Exception
     */
    public function evaluate($data = null)
    {
        $userId = $this->user->getKey();
        $throttle = UserThrottle::findByUser($userId);
        if ($throttle->isBlocked()) {
            return $throttle->checkTimeout();
        } else {
            return $throttle->checkAttempts();
        }
    }


}