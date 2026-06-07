<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Auth\MagicLink;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class MagicLinkNotification extends Notification
{
    public function __construct(
        private readonly string $email,
        private readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::route('login.verify', [
            'email' => $this->email,
            'token' => $this->token,
        ]);

        return (new MailMessage)
            ->subject('Twój link do logowania — Podwórko')
            ->line('Kliknij poniższy przycisk, aby zalogować się do Podwórka.')
            ->action('Zaloguj się', $url)
            ->line('Link jest ważny przez '.MagicLink::TTL_MINUTES.' minut i działa tylko raz.')
            ->line('Jeśli to nie Ty prosiłeś o logowanie, zignoruj tę wiadomość.');
    }
}
