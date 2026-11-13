<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\SecurityChallenge;
use App\Models\User;
use App\Services\AdminAccessGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecoverSecurityIncident extends Command
{
    use ProfileValidationRules;

    protected $signature = 'security:recover {user : ID of the affected account}';

    protected $description = 'Prepare a reported account for password recovery after an identity check';

    public function handle(): int
    {
        $user = User::find($this->argument('user'));
        if (! $user || $user->security_recovery_required !== 'assisted' || app(AdminAccessGuard::class)->isOwner($user)) {
            $this->error('This account does not qualify for assisted recovery. Use owner:recover for the installation owner.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive() || ! $this->confirm('Have you independently verified this user? Existing MFA and passkeys will be removed.')) {
            $this->error('Assisted recovery requires interactive confirmation.');

            return self::FAILURE;
        }
        $email = $this->ask('Verified recovery email address', $user->email);
        Validator::make(['email' => $email], ['email' => $this->emailRules($user->id)])->validate();
        DB::transaction(function () use ($user, $email) {
            SecurityChallenge::where('user_id', $user->id)->update(['report_expires_at' => now()->subSecond()]);
            $user->passkeys()->delete();
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => $email === $user->email ? $user->email_verified_at : null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'security_recovery_required' => 'password',
            ])->save();
            activity('security')->event('security-change.assisted-recovery')->performedOn($user)->causedByAnonymous()
                ->log('security-change.assisted-recovery');
        });
        $this->info('Assisted recovery prepared. The user must reset their password using the verified recovery address.');

        return self::SUCCESS;
    }
}
