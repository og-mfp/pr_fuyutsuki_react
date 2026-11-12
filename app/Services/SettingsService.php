<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SettingsService
{
    private const CACHE_KEY = 'app-settings:non-sensitive:v1';

    private ?bool $hasSettingsTable = null;

    /** @var array<string, mixed> */
    private array $loaded = [];

    public function __construct(private SettingsRegistry $registry) {}

    public function get(string $key): mixed
    {
        return $this->getMany([$key])[$key];
    }

    /** @param list<string> $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys): array
    {
        $missing = [];
        foreach (array_unique($keys) as $key) {
            $this->registry->definition($key);
            if (! array_key_exists($key, $this->loaded)) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            if (! ($this->hasSettingsTable ??= Schema::hasTable('app_settings'))) {
                foreach ($missing as $key) {
                    $this->loaded[$key] = $this->registry->definition($key)['default'];
                }
            } else {
                $definitions = $this->registry->all();
                foreach ([false, true] as $cacheable) {
                    if (! collect($missing)->contains(fn ($key) => $this->cacheable($key) === $cacheable)) {
                        continue;
                    }

                    $keysToLoad = array_keys(array_filter($definitions, fn ($definition, $key) => $this->cacheable($key) === $cacheable && ! array_key_exists($key, $this->loaded), ARRAY_FILTER_USE_BOTH));
                    $keysToFetch = $cacheable
                        ? array_keys(array_filter($definitions, fn ($definition, $key) => $this->cacheable($key), ARRAY_FILTER_USE_BOTH))
                        : $keysToLoad;
                    $fetch = fn () => AppSetting::whereIn('key', $keysToFetch)->pluck('value', 'key')->all();
                    if (! $cacheable || DB::transactionLevel() > 0) {
                        $stored = $fetch();
                    } else {
                        try {
                            $stored = Cache::remember(self::CACHE_KEY, 60, $fetch);
                        } catch (Throwable $exception) {
                            report($exception);
                            $stored = $fetch();
                        }
                    }
                    foreach ($keysToLoad as $key) {
                        $this->loaded[$key] = $stored[$key] ?? $definitions[$key]['default'];
                    }
                }
            }
        }

        $values = array_intersect_key($this->loaded, array_flip($keys));
        if (isset($values['smtp.password']) && $values['smtp.password'] !== '') {
            $values['smtp.password'] = Crypt::decryptString($values['smtp.password']);
        }

        return $values;
    }

    private function cacheable(string $key): bool
    {
        // Alert delivery must stay fresh without changing its update authorization.
        return ! $this->registry->definition($key)['sensitive'] && $key !== 'notifications.security_enabled';
    }

    private function invalidateCache(): void
    {
        $forget = function (): void {
            try {
                Cache::forget(self::CACHE_KEY);
            } catch (Throwable $exception) {
                report($exception);
            }
        };
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);
        } else {
            $forget();
        }
    }

    /** @return array<string, mixed> */
    public function category(string $category): array
    {
        $keys = array_keys(array_filter($this->registry->all(), fn ($definition) => $definition['category'] === $category));
        $values = $this->getMany($keys);
        $categoryValues = [];
        foreach ($keys as $key) {
            $categoryValues[substr($key, strlen($category) + 1)] = $values[$key];
        }

        return $categoryValues;
    }

    /** @param array<string, mixed> $values */
    public function update(string $category, array $values, User $actor): void
    {
        $rules = [];
        foreach ($values as $name => $value) {
            $key = $category.'.'.$name;
            $definition = $this->registry->definition($key);
            if ($definition['category'] !== $category) {
                throw ValidationException::withMessages([$name => 'Invalid setting.']);
            }
            $rules[$name] = $definition['rules'];
        }
        $validated = Validator::make($values, $rules)->validate();
        DB::transaction(function () use ($category, $validated, $actor) {
            foreach ($validated as $name => $value) {
                $key = $category.'.'.$name;
                $definition = $this->registry->definition($key);
                $default = $definition['default'];
                $value ??= $default;
                $value = match ($definition['type']) {
                    'boolean' => (bool) $value,
                    'integer' => (int) $value,
                    default => $value,
                };
                $old = AppSetting::where('key', $key)->lockForUpdate()->first();
                $before = $old->value ?? $default;
                if ($key === 'smtp.password') {
                    $before = $before !== '' ? Crypt::decryptString($before) : '';
                }
                if ($before === $value) {
                    continue;
                }
                AppSetting::updateOrCreate(['key' => $key], ['value' => $key === 'smtp.password' && $value !== '' ? Crypt::encryptString($value) : $value, 'updated_by' => $actor->id]);
                activity('configuration')->causedBy($actor)->withProperties($key === 'smtp.password' ? ['key' => $key] : ['key' => $key, 'old' => $before, 'new' => $value])->log('setting changed');
            }
        });
        foreach (array_keys($validated) as $name) {
            unset($this->loaded[$category.'.'.$name]);
        }
        if (collect(array_keys($validated))->contains(fn ($name) => $this->cacheable($category.'.'.$name))) {
            $this->invalidateCache();
        }
    }

    public function initialize(): void
    {
        $this->hasSettingsTable = true;
        $this->loaded = [];
        foreach ($this->registry->all() as $key => $definition) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $definition['default']]);
        }
        $this->invalidateCache();
    }
}
