<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UiGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): array
    {
        return json_decode(file_get_contents(resource_path('js/lib/ui-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_catalog_lists_and_renders_every_independent_page(): void
    {
        $this->actingAs($this->privilegedUser());
        $catalog = $this->catalog();

        $this->get('/admin/ui')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ui/Index')->where('components', $catalog));

        foreach ($catalog as $entry) {
            $this->get('/admin/ui/'.$entry['slug'])->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($entry['page'])->where('component', $entry));
        }

        $this->actingAs($this->installationOwner())->get('/admin/ui')->assertOk();
    }

    public function test_catalog_and_legacy_routes_require_authentication_and_admin_access(): void
    {
        foreach (['/admin/ui', '/admin/ui/button', '/ui', '/ui/button'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }

        $this->actingAs(User::factory()->create());
        foreach (['/admin/ui', '/admin/ui/button', '/ui', '/ui/button'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_unknown_names_are_not_filesystem_paths_and_legacy_links_redirect(): void
    {
        $this->actingAs($this->privilegedUser());
        foreach (['unknown-component', 'form', 'Button', 'components%2FButton', '..%2Fadmin%2FUsers'] as $name) {
            $this->get('/admin/ui/'.$name)->assertNotFound();
            $this->get('/ui/'.$name)->assertNotFound();
        }
        $this->get('/ui')->assertRedirect('/admin/ui');
        $this->get('/ui/button')->assertRedirect('/admin/ui/button');
    }

    public function test_manifest_covers_installed_components_and_contains_only_real_pages(): void
    {
        $catalog = $this->catalog();
        $slugs = array_column($catalog, 'slug');
        $pages = array_column($catalog, 'page');
        $this->assertCount(count(array_unique($slugs)), $slugs);
        $this->assertCount(count(array_unique($pages)), $pages);
        foreach (glob(resource_path('js/components/ui/*/index.ts')) as $barrel) {
            $this->assertContains(basename(dirname($barrel)), $slugs);
        }
        foreach (['combobox', 'date-picker', 'data-table', 'typography'] as $composition) {
            $this->assertContains($composition, $slugs);
        }
        foreach ($catalog as $entry) {
            $this->assertMatchesRegularExpression('/^[a-z]+(?:-[a-z]+)*$/', $entry['slug']);
            $this->assertMatchesRegularExpression('#^ui/components/[A-Za-z]+$#', $entry['page']);
            $path = resource_path('js/pages/'.$entry['page'].'.svelte');
            $this->assertFileExists($path);
            $source = file_get_contents($path);
            $this->assertStringNotContainsString('import.meta.glob', $source);
            $this->assertStringNotContainsString('Preview.svelte', $source);
            $this->assertStringNotContainsString('/previews/', $source);
            $this->assertStringContainsString('<Showcase', $source);
            $this->assertStringContainsString('const usage', $source);
        }
        $this->assertCount(count($pages), glob(resource_path('js/pages/ui/components/*.svelte')));
        $this->assertFileDoesNotExist(resource_path('js/pages/ui/Component.svelte'));
        $this->assertFileDoesNotExist(resource_path('js/pages/ui/Preview.svelte'));
        $this->assertEmpty(glob(resource_path('js/pages/ui/previews/*.svelte')));
    }

    public function test_settings_redirect_preserves_methods_and_destination(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'QUERY'] as $method) {
            $this->call($method, '/settings')->assertRedirect('/settings/profile');
        }
    }
}
