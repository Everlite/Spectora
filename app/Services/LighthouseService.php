<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LighthouseService
{
    /**
     * Scan a URL using local Lighthouse CLI.
     */
    public function scan(string $url, string $strategy = 'mobile'): ?array
    {
        try {
            $cmd = [
                'lighthouse',
                $url,
                '--output=json',
                '--quiet',
                '--chrome-flags="--no-sandbox --headless --disable-gpu --disable-dev-shm-usage"',
                "--emulated-form-factor=$strategy",
                '--only-categories=performance'
            ];

            $process = new Process($cmd);
            $process->setTimeout(150); // Increased timeout for full scans
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error("Lighthouse CLI Error for $url ($strategy): " . $process->getErrorOutput());
                return null;
            }

            return json_decode($process->getOutput(), true);

        } catch (\Exception $e) {
            Log::error("LighthouseService Exception for $url ($strategy): " . $e->getMessage());
            return null;
        }
    }
}
