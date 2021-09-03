<?php

namespace App\Services\ImageServices;

use App\Domains\ImageManageContract;
use App\Models\Image;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SelfHostedImageService implements ImageManageContract
{
    public const NAME = 'self-hosted';
    public const BASE_PATH = 'img-self-hosted';

    public function __construct(protected Filesystem $filesystem)
    {
    }

    /**
     * Get the service name
     *
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Upload an UploadedFile to Self-hosted Storage
     *
     * @param UploadedFile $file
     * @param User $user
     *
     * @return Image|null
     */
    public function upload(UploadedFile $file, User $user): ?Image
    {
        // 2021-09-01-uuid.png/jgp/...
        $newFileName = sprintf(
            '%s-%s.%s',
            Carbon::now()->toDateString(),
            Str::orderedUuid(),
            $file->getClientOriginalExtension()
        );

        $filePath = $file->storePubliclyAs(self::BASE_PATH, $newFileName);

        return Image::create([
            'user_id' => $user->id,
            'filename' => $newFileName,
            'url' => public_path(self::BASE_PATH . '/' . $newFileName),
            'service' => $this->getName(),
            'payload' => [
                'filePath' => $filePath,
                'fileSize' => $file->getSize(),
            ],
        ]);
    }

    /**
     * Delete a self-hosted image
     *
     * @param Image $image
     *
     * @return bool
     */
    public function delete(Image $image): bool
    {
        // delete from storage
        $this->filesystem->delete($image->payload['filePath']);

        // then delete record
        return $image->delete();
    }
}
