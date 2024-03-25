<?php
// src/Event/RegistrationCompletedEvent.php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\User;

class RegistrationCompletedEvent extends Event
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
