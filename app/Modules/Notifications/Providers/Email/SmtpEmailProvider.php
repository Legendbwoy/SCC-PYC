<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Providers\Email;

use App\Modules\Notifications\Contracts\EmailProviderInterface;
use App\Modules\Notifications\DTOs\EmailMessage;
use App\Modules\Notifications\Exceptions\NotificationProviderException;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * SMTP / Laravel Mail Email Provider (Spec Section 10.1, 10.3 & 10.5).
 * Successful handoff signifies mail-server acceptance ('sent'), not guaranteed inbox delivery.
 */
class SmtpEmailProvider implements EmailProviderInterface
{
    public function send(EmailMessage $message): void
    {
        $mailerName = (string) config('notifications.email.mailer', config('mail.default', 'smtp'));
        $replyTo = trim((string) config('notifications.email.reply_to', ''));

        try {
            Mail::mailer($mailerName)->html($message->htmlBody, static function (Message $mail) use ($message, $replyTo): void {
                $mail->to($message->recipientEmail, $message->recipientName)
                    ->subject($message->subject);

                if ($replyTo !== '') {
                    $mail->replyTo($replyTo);
                }

                if ($message->textBody !== null && $message->textBody !== '') {
                    $mail->text($message->textBody);
                }

                foreach ($message->attachmentPaths as $path) {
                    if (is_file($path)) {
                        $mail->attach($path);
                    }
                }
            });
        } catch (TransportExceptionInterface $exception) {
            $msg = $exception->getMessage();
            $lower = strtolower($msg);

            $isAuthError = str_contains($lower, '535')
                || str_contains($lower, 'authentication')
                || str_contains($lower, 'credentials')
                || str_contains($lower, 'username and password not accepted');

            $isInvalidRecipient = str_contains($lower, '550')
                || str_contains($lower, 'invalid address')
                || str_contains($lower, 'recipient rejected');

            throw new NotificationProviderException(
                message: 'SMTP transport failure: '.$msg,
                retryable: ! $isAuthError && ! $isInvalidRecipient,
                isAuthFailure: $isAuthError,
                previous: $exception,
            );
        } catch (\Throwable $exception) {
            throw new NotificationProviderException(
                message: 'Email dispatch failure: '.$exception->getMessage(),
                retryable: false,
                previous: $exception,
            );
        }
    }
}
