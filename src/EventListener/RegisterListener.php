<?php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Event\RegistrationCompletedEvent;
use App\Manager\Mercure\MercureManager;

class RegisterListener implements EventSubscriberInterface
{
    public function __construct(private MercureManager $mercureManager)
    {}

    public static function getSubscribedEvents()
    {
        return [
            RegistrationCompletedEvent::class => 'onRegistrationCompleted',
        ];
    }

    public function onRegistrationCompleted(RegistrationCompletedEvent $event)
    {
        $user = $event->getUser();    
        // The Publisher service is an invokable object
        $this->mercureManager->publish(
            'https://example.com/books/1',
            'utilisateur',
            [
                'email' => $user->getEmail(),
                'type' => $user->getType(),
                'id' => $user->getId(),
            ],
            'Nouvel Utilisateur : '.$user->getEmail()
            );
    }
}