<?php

namespace App\Services;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerProvider
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $emailFrom,
    ) {}

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from($this->emailFrom)
            ->to($to)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }

    public function contactEmail($contact): void
    {
        $body = $this->twig->render('emails/contact.html.twig', [
            'name' => $contact->getName(),
            'email' => $contact->getEmail(),
            'subject' => $contact->getSubject(),
            'message' => $contact->getMessage(),
        ]);

        $this->sendEmail($this->emailFrom, 'Nouveau message Pizza Earth', $body);
    }
}
