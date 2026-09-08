<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');

        foreach (['expense-claims', 'payment-proofs', 'member-documents'] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $stream = $public->readStream($path);

                if ($stream === false) {
                    continue;
                }

                try {
                    $private->makeDirectory(dirname($path));
                    $private->writeStream($path, $stream);
                } finally {
                    fclose($stream);
                }

                $public->delete($path);
            }
        }
    }

    public function down(): void
    {
        $private = Storage::disk('private');
        $public = Storage::disk('public');

        foreach (['expense-claims', 'payment-proofs', 'member-documents'] as $directory) {
            foreach ($private->allFiles($directory) as $path) {
                $stream = $private->readStream($path);

                if ($stream === false) {
                    continue;
                }

                try {
                    $public->makeDirectory(dirname($path));
                    $public->writeStream($path, $stream);
                } finally {
                    fclose($stream);
                }

                $private->delete($path);
            }
        }
    }
};
