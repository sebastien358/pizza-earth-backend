<?php

namespace App\Services;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use function Symfony\Component\String\u;

class MailerProvider
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $emailFrom
    ) {
    }

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

    public function registerEmailPizzeria($user): void
    {
        $body = $this->twig->render('emails/registerPizzeria.html.twig', [
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone()
        ]);

        $this->sendEmail($this->emailFrom, 'Nouveau message Pizza Earth', $body);
    }

    public function registerEmailClient($user): void
    {
        $body = $this->twig->render('emails/registerClient.html.twig', [
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname()
        ]);

        $this->sendEmail($user->getEmail(), 'Nouveau message Pizza Earth', $body);
    }

    public function orderEmailPizzeria($order): void
    {
        $body = $this->twig->render('emails/orderPizzeria.html.twig', [
            'order' => $order,
            'name' => $order->getName(),
            'email' => $order->getEmail(),
            'phone' => $order->getPhone(),
            'total' => $order->getTotal(),
            'items' => $order->getOrderItems(),
        ]);
        $this->sendEmail($this->emailFrom, 'Nouvelle commande #' . $order->getId(), $body);
    }

    public function orderEmailClient($order): void
    {
        $body = $this->twig->render('emails/orderClient.html.twig', [
            'order' => $order,
            'name' => $order->getName(),
            'total' => $order->getTotal(),
            'items' => $order->getOrderItems(),
        ]);
        $this->sendEmail($order->getEmail(), 'Ta commande Pizza Earth #' . $order->getId() . ' est confirmée', $body);
    }

    public function requestPassword($user, $url): void
    {
        $body = $this->twig->render('emails/requestPassword.html.twig', [
            'name' => $user->getFirstname() . ' ' . $user->getLastname(),
            'url' => $url
        ]);
        $this->sendEmail($user->getEmail(), 'Demande de rénitialisation de mot de passe', $body);
    }
}
