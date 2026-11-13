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

    public function test_catalog_lists_and_returns_every_component_preview(): void
    {
        $this->actingAs($this->privilegedUser());
        $catalog = $this->catalog();

        $this->get('/admin/ui')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ui/Index')->where('components', $catalog));

        foreach ($catalog as $entry) {
            $this->get('/admin/ui/'.$entry['slug'])->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($entry['page'], false)->where('component', $entry));
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

    public function test_manifest_covers_installed_components_and_uses_react_preview_aliases(): void
    {
        $catalog = $this->catalog();
        $slugs = array_column($catalog, 'slug');
        $pages = array_column($catalog, 'page');
        $this->assertNotEmpty($catalog);
        $this->assertSame(json_decode(file_get_contents(resource_path('ui-catalog.json')), true, 512, JSON_THROW_ON_ERROR), $catalog);
        $this->assertCount(count(array_unique($slugs)), $slugs);
        $this->assertCount(count(array_unique($pages)), $pages);
        foreach (glob(resource_path('js/components/ui/*.tsx')) as $component) {
            $slug = basename($component, '.tsx');
            if (! in_array($slug, ['icon', 'placeholder-pattern'], true)) {
                $this->assertContains($slug, $slugs);
            }
        }
        foreach (['combobox', 'date-picker', 'data-table', 'typography'] as $composition) {
            $this->assertContains($composition, $slugs);
        }
        foreach ($catalog as $entry) {
            $this->assertMatchesRegularExpression('/^[a-z]+(?:-[a-z]+)*$/', $entry['slug']);
            $this->assertMatchesRegularExpression('#^ui/components/[A-Za-z]+$#', $entry['page']);
        }
        $this->assertFileExists(resource_path('js/pages/ui/ComponentPreview.tsx'));
        $resolver = file_get_contents(resource_path('js/app.tsx'));
        $this->assertStringContainsString("name.startsWith('ui/components/')", $resolver);
        $this->assertStringContainsString("'ui/ComponentPreview'", $resolver);
    }

    public function test_settings_redirect_preserves_methods_and_destination(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'QUERY'] as $method) {
            $this->call($method, '/settings')->assertRedirect('/settings/profile');
        }
    }
}
