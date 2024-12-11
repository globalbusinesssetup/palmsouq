<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteCPTKImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:delete-cptk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all images containing "CPTK" from the public/uploads directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $uploadsDir = public_path('uploads');

        if (File::exists($uploadsDir)) {
            $files = File::files($uploadsDir);
            $deletedFiles = 0;

            foreach ($files as $file) {
                $fileName = $file->getFilename();

                if (str_contains($fileName, 'CPTK')) {
                    File::delete($file->getPathname());
                    $this->info("Deleted file: {$fileName}");
                    $deletedFiles++;
                }
            }

            $this->info("Deleted {$deletedFiles} file(s) containing 'CPTK' from the uploads directory.");
            return Command::SUCCESS;
        }

        $this->error('Uploads directory does not exist.');
        return Command::FAILURE;
    }
}