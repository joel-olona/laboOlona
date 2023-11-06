<?php 

namespace App\Service\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class MailerService 
{
    public function __construct(private MailerInterface $mailer){}

    public function send(
        string $to,
        string $subject,
        string $template,
        array $context
    ): void
    {
        $email = new TemplatedEmail();
        $email 
            ->from(new Address('noreply@olona-talents.com', 'Olona Talents'))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("mails/$template")
            ->context($context)
            ;

        try{

            $this->mailer->send($email);

        }catch(TransportExceptionInterface $transportException){

            throw $transportException;

        }
    }
}