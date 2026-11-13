<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RecoverInstallationOwner extends Command
{
    use ProfileValidationRules;

    protected $signature = 'owner:recover {email : Email address of the installation owner}';

    protected $description = 'Set a new password for the installation owner through the console';

    public function handle(): int
    {
        $ownerId = DB::table('installation_metadata')->where('id', 1)->value('owner_id');
        $owner = $ownerId ? User::query()->whereKey($ownerId)->first() : null;
        if (! $owner || ! hash_equals($owner->email, (string) $this->argument('email'))) {
            $this->error('That email does not identify the installation owner.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive() || ! $this->confirm("Reset the password for installation owner {$owner->email}?")) {
            $this->error('Owner recovery requires an interactive confirmation.');

            return self::FAILURE;
        }

        $password = $this->secret('New password');
        $confirmation = $this->secret('Confirm new password');
        Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ])->validate();

        $email = $owner->email;
        if ($owner->security_recovery_required === 'assisted') {
            $email = $this->ask('Verified recovery email address', $owner->email);
            Validator::make(['email' => $email], ['email' => $this->emailRules($owner->id)])->validate();
            if (! $this->confirm('Remove the owner’s MFA and passkeys as part of incident recovery?')) {
                $this->error('Incident recovery requires removal of existing authentication factors.');

                return self::FAILURE;
            }
        }
        DB::transaction(function () use ($owner, $password, $email) {
            SecurityChallenge::where('user_id', $owner->id)->update(['report_expires_at' => now()->subSecond()]);
            if ($owner->security_recovery_required === 'assisted') {
                $owner->passkeys()->delete();
                $owner->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
            }
            $owner->forceFill([
                'email_verified_at' => $email === $owner->email ? $owner->email_verified_at : null,
                'email' => $email, 'password' => $password, 'remember_token' => Str::random(60),
                'security_recovery_required' => null,
            ])->save();
        });
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $owner->id)->delete();
        }
        $this->info('Installation owner password updated.');

        return self::SUCCESS;
    }
}
