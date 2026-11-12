<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfileAvatarController extends Controller
{
    public function show(Request $request): BinaryFileResponse
    {
        $path = $request->user()->avatar_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'dimensions:max_width=1024,max_height=1024'],
        ]);

        $file = $request->file('avatar');
        $imageInfo = @getimagesize($file->getPathname());
        $mime = $imageInfo['mime'] ?? null;
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['avatar' => 'The avatar must be a valid JPEG, PNG, or WebP image.']);
        }

        $bytes = $file->get();
        if (! is_string($bytes)) {
            throw ValidationException::withMessages(['avatar' => 'The avatar image could not be read.']);
        }
        try {
            $image = imagecreatefromstring($bytes);
        } catch (Throwable) {
            $image = false;
        }
        if ($image === false) {
            throw ValidationException::withMessages(['avatar' => 'The avatar image could not be decoded.']);
        }

        $output = imagecreatetruecolor(512, 512);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        if ($transparent === false) {
            imagedestroy($image);
            imagedestroy($output);
            throw ValidationException::withMessages(['avatar' => 'The avatar image could not be processed.']);
        }
        imagefill($output, 0, 0, $transparent);
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $scale = max(512 / $sourceWidth, 512 / $sourceHeight);
        $width = (int) ceil($sourceWidth * $scale);
        $height = (int) ceil($sourceHeight * $scale);
        imagecopyresampled($output, $image, (512 - $width) / 2, (512 - $height) / 2, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        $encoded = imagewebp($output, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);
        imagedestroy($output);
        if (! $encoded || ! is_string($contents)) {
            throw ValidationException::withMessages(['avatar' => 'The avatar image could not be processed.']);
        }

        $disk = Storage::disk('local');
        $user = $request->user();
        $oldPath = $user->avatar_path;
        $newPath = 'avatars/'.$user->id.'/'.Str::uuid().'.webp';
        if (! $disk->put($newPath, $contents)) {
            throw ValidationException::withMessages(['avatar' => 'The avatar could not be stored.']);
        }

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            $disk->delete($newPath);
            throw $exception;
        }
        if ($oldPath) {
            $disk->delete($oldPath);
        }

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $path = $user->avatar_path;
        $user->avatar_path = null;
        $user->save();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back();
    }
}
