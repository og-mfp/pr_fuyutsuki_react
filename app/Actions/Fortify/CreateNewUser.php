<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\InstallationStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\InstallationService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private InstallationService $installation, private SettingsService $settings) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if ($this->installation->status() !== InstallationStatus::Installed) {
            throw ValidationException::withMessages([
                'email' => 'Complete the first-time setup before registering users.',
            ]);
        }

        if ($this->settings->get('users.registration_mode') !== 'public'
            || $this->settings->get('authentication.authentication_lockdown')) {
            throw ValidationException::withMessages(['email' => 'Registration is disabled.']);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
            if ($this->settings->get('users.registration_approval_required')) {
                $user->forceFill(['frozen_at' => now()])->save();
            }
            $group = Group::where('slug', $this->settings->get('users.default_role'))
                ->whereNotIn('slug', ['owner'])->whereDoesntHave('roles.permissions')->firstOrFail();
            $user->groups()->attach($group);

            return $user;
        });
    }
}
