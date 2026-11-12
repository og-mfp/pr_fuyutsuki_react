<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\SecurityChallenge;
use App\Models\User;
use App\Services\AdminAccessGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        abort_if(app(AdminAccessGuard::class)->isOwner($user), 403, 'Use the controlled owner recovery procedure.');
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        DB::transaction(function () use ($user, $input) {
            SecurityChallenge::where('user_id', $user->id)->update(['report_expires_at' => now()->subSecond()]);
            $user->refresh();
            $user->forceFill([
                'password' => $input['password'],
                'security_recovery_required' => $user->security_recovery_required === 'password' ? null : $user->security_recovery_required,
            ])->save();
        });
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }
    }
}
