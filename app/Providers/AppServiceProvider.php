<?php

namespace App\Providers;

use App\Services\Invites;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureMails();

        // Signed up through an invite link: follow the inviter once the email is confirmed.
        Event::listen(Verified::class, fn (Verified $event) => app(Invites::class)->follow($event->user));
    }

    /**
     * Hoot's own words for the two account mails. The look is in resources/views/vendor/mail.
     */
    private function configureMails(): void
    {
        VerifyEmail::toMailUsing(fn (object $user, string $url) => (new MailMessage)
            ->subject('Confirm your email for Hoot')
            ->greeting('Hi '.$user->name.',')
            ->line('Welcome to Hoot. Confirm your email and your For you page opens, ready for the first show you follow.')
            ->action('Confirm my email', $url)
            ->line('The link works for an hour. If you didn’t sign up for Hoot, you can ignore this mail.')
            ->salutation('Hoot keeps watch from here.'));

        ResetPassword::toMailUsing(fn (object $user, string $token) => (new MailMessage)
            ->subject('Pick a new Hoot password')
            ->greeting('Hi '.$user->name.',')
            ->line('Someone, hopefully you, asked to reset the password for your Hoot account.')
            ->action('Pick a new password', url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
            ->line('The link works for '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes. If you didn’t ask for this, your password stays as it is.')
            ->salutation('Hoot'));
    }
}
