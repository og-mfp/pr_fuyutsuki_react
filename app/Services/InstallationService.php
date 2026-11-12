<?php

namespace App\Services;

use App\Enums\InstallationStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class InstallationService
{
    private const REQUIRED_TABLES = [
        'app_settings',
        'users',
        'groups',
        'permissions',
        'group_user',
        'rbac_roles',
        'role_permission',
        'installation_metadata',
    ];

    public function isInstalled(): bool
    {
        return $this->status() === InstallationStatus::Installed;
    }

    public function status(): InstallationStatus
    {
        if (! $this->databaseReady()) {
            return InstallationStatus::Unknown;
        }

        try {
            if (User::query()->exists()) {
                return app(InstallationIntegrityService::class)->ownerIsConsistent()
                    ? InstallationStatus::Installed : InstallationStatus::Inconsistent;
            }

            $metadata = $this->databaseMarkerExists();
            $lock = is_file($this->lockPath());

            return match ([$metadata, $lock]) {
                [false, false] => InstallationStatus::NotInstalled,
                default => InstallationStatus::Inconsistent,
            };
        } catch (Throwable) {
            return InstallationStatus::Unknown;
        }
    }

    /**
     * @return list<array{label: string, ready: bool, detail: string}>
     */
    public function checks(): array
    {
        $databaseReady = $this->databaseReady();
        try {
            $accessReady = $databaseReady && Group::query()
                ->where('slug', 'owner')
                ->whereHas('roles.permissions', fn ($query) => $query->where('slug', 'admin.access'))
                ->exists();
        } catch (Throwable) {
            $accessReady = false;
        }
        $status = $this->status();

        return [
            ['label' => 'Application key', 'ready' => filled(config('app.key')), 'detail' => filled(config('app.key')) ? '' : 'Set APP_KEY before continuing.'],
            ['label' => 'Application URL', 'ready' => filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false, 'detail' => filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false ? '' : 'Set APP_URL to a valid URL.'],
            ['label' => 'Database connection and migrations', 'ready' => $databaseReady, 'detail' => $databaseReady ? '' : 'Verify database configuration, connectivity, and completed migrations.'],
            ['label' => 'Owner permissions', 'ready' => $accessReady, 'detail' => $accessReady ? '' : 'Run migrations so the Owner role and admin.access permission exist.'],
            ['label' => 'Writable installation storage', 'ready' => is_writable(dirname($this->lockPath())), 'detail' => is_writable(dirname($this->lockPath())) ? '' : 'Make storage/app/private writable by the application user.'],
            ['label' => 'Installation state', 'ready' => $status === InstallationStatus::NotInstalled, 'detail' => match ($status) {
                InstallationStatus::NotInstalled => '',
                InstallationStatus::Installed => 'An existing installation was found; sign in instead of creating another administrator.',
                InstallationStatus::Inconsistent => 'Installation markers are incomplete. An authorized operator must investigate before setup can continue.',
                InstallationStatus::Unknown => 'Installation state cannot be verified. Verify database access and required migrations before continuing.',
            }],
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function install(array $attributes): ?User
    {
        return Cache::lock('first-time-setup', 10)->get(function () use ($attributes): ?User {
            if ($this->status() !== InstallationStatus::NotInstalled || ! $this->ready()) {
                return null;
            }

            return DB::transaction(function () use ($attributes): User {
                $user = User::query()->create($attributes);
                $user->groups()->attach(Group::where('slug', 'owner')->firstOrFail());
                app(SettingsService::class)->initialize();

                DB::table('installation_metadata')->insert([
                    'id' => 1,
                    'installed_at' => now(),
                    'owner_id' => $user->id,
                ]);

                $this->writeLockFile($user);

                return $user;
            });
        }) ?: null;
    }

    private function ready(): bool
    {
        return ! in_array(false, array_column($this->checks(), 'ready'), true);
    }

    private function databaseReady(): bool
    {
        try {
            DB::connection()->getPdo();

            return collect(self::REQUIRED_TABLES)
                ->every(fn (string $table): bool => Schema::hasTable($table));
        } catch (Throwable) {
            return false;
        }
    }

    private function databaseMarkerExists(): bool
    {
        return Schema::hasTable('installation_metadata')
            && DB::table('installation_metadata')->exists();
    }

    private function lockPath(): string
    {
        return storage_path('app/private/installed.lock');
    }

    private function writeLockFile(User $user): void
    {
        $temporaryPath = $this->lockPath().'.tmp';
        $contents = json_encode([
            'installed_at' => now()->toIso8601String(),
            'superuser_id' => $user->getKey(),
        ], JSON_THROW_ON_ERROR);

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false
            || ! rename($temporaryPath, $this->lockPath())) {
            @unlink($temporaryPath);

            throw new RuntimeException('Unable to create the installation lockfile.');
        }
    }
}
