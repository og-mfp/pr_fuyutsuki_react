<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_see_and_remove_their_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.store'), ['avatar' => $this->image()])
            ->assertRedirect();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('image/webp', mime_content_type(Storage::disk('local')->path($path)));

        $this->get(route('profile.avatar.show'))->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('profile.edit'))->assertInertia(fn ($page) => $page->where('auth.user.avatar', route('profile.avatar.show')));
        $this->actingAs(User::factory()->create())->get(route('profile.avatar.show'))->assertNotFound();

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_avatar_file_route_requires_authentication(): void
    {
        $this->get(route('profile.avatar.show'))->assertRedirect(route('login'));
    }

    public function test_account_deletion_removes_the_stored_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.store'), ['avatar' => $this->image()]);
        $path = $user->fresh()->avatar_path;

        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_upload_replaces_the_old_file_and_rejects_invalid_or_oversized_images(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.store'), ['avatar' => $this->image()]);
        $oldPath = $user->fresh()->avatar_path;

        $this->post(route('profile.avatar.store'), ['avatar' => $this->image(80, 120)])
            ->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);

        $this->post(route('profile.avatar.store'), ['avatar' => UploadedFile::fake()->createWithContent('fake.png', 'not an image')])
            ->assertSessionHasErrors('avatar');
        $this->post(route('profile.avatar.store'), ['avatar' => UploadedFile::fake()->create('large.png', 2049, 'image/png')])
            ->assertSessionHasErrors('avatar');
        $this->post(route('profile.avatar.store'), ['avatar' => $this->image(1025, 10)])
            ->assertSessionHasErrors('avatar');
    }

    private function image(int $width = 64, int $height = 64): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('avatar.png', $contents);
    }
}
