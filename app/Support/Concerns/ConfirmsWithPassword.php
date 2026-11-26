<?php

namespace App\Support\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

trait ConfirmsWithPassword
{
    public string $confirmPassword = '';

    /** Re-verify the signed-in user's password before a sensitive change. */
    protected function confirmPasswordOrFail(): bool
    {
        $user = auth()->user();

        if ($user === null || ! Hash::check($this->confirmPassword, $user->password)) {
            $this->addError('confirmPassword', 'Enter your password to confirm this change.');

            return false;
        }

        $this->reset('confirmPassword');
        Session::put('auth.password_confirmed_at', now()->timestamp);

        return true;
    }
}
