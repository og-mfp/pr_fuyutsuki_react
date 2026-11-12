<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\InstallationStatus;
use App\Services\InstallationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstTimeSetupController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private InstallationService $installation) {}

    public function create(): Response|RedirectResponse
    {
        $status = $this->installation->status();
        if ($status === InstallationStatus::Installed) {
            return redirect()->route(Auth::check() ? 'dashboard' : 'login');
        }
        abort_if($status === InstallationStatus::Inconsistent, 503, 'Installation status cannot be verified.');

        return Inertia::render('setup/Index', [
            'checks' => $this->installation->checks(),
            'passwordRules' => $status === InstallationStatus::NotInstalled ? Password::defaults()->toPasswordRulesString() : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $status = $this->installation->status();
        if ($status === InstallationStatus::Installed) {
            return redirect()->route(Auth::check() ? 'dashboard' : 'login');
        }
        abort_if($status !== InstallationStatus::NotInstalled, 503, 'Installation status cannot be verified.');

        $checks = $this->installation->checks();

        if (in_array(false, array_column($checks, 'ready'), true)) {
            return back()->withErrors(['configuration' => 'Complete the failed configuration checks before continuing.']);
        }

        $input = Validator::make($request->all(), [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = $this->installation->install([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        if (! $user) {
            return redirect()->route('login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
