<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class ForgotPasswordForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate();

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}
