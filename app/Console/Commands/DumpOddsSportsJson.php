<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class DumpOddsSportsJson extends Command
{
    protected $signature = 'odds:dump-sports
        {--sport= : Только один sport_key}
        {--overwrite : Перезаписывать существующие файлы}
        {--sleep=250 : Пауза между запросами в мс}';

    protected $description = 'Dump The Odds API odds per sport_key into database/seeders/data/sports/{sport_key}.json';

    public function handle(): int
    {
        $apiKey = '0de7eb628bf9d325cd43148b8c0a8a30';
        if (!$apiKey) {
            $this->error('ODDS_API_KEY is not set in .env');
            return self::FAILURE;
        }

        $baseUrl = 'https://api.the-odds-api.com/v4/sports';
        $dir = base_path('database/seeders/data/sports');

        File::ensureDirectoryExists($dir);

        $onlySport = $this->option('sport');
        $overwrite = (bool) $this->option('overwrite');
        $sleepMs = max(0, (int) $this->option('sleep'));

        $sportKeys = $onlySport
            ? collect([$onlySport])
            : DB::table('sports')
                ->whereNotNull('sport_key')
                ->where('sport_key', '<>', '')
                ->orderBy('sport_key')
                ->pluck('sport_key');

        $this->info('Sports count: ' . $sportKeys->count());

        $failed = [];

        foreach ($sportKeys as $sportKey) {
            $safeName = preg_replace('~[^a-zA-Z0-9._-]+~', '_', (string) $sportKey);
            $filePath = $dir . DIRECTORY_SEPARATOR . $safeName . '.json';

            if (!$overwrite && File::exists($filePath)) {
                $this->line("skip: {$sportKey} (exists)");
                continue;
            }

            $url = "{$baseUrl}/{$sportKey}/odds/";

            $query = [
                'regions' => 'eu',
                'bookmakers' => 'betsson',
                'apiKey' => $apiKey,
            ];

            // retry/backoff на 429 + сетевые ошибки
            $response = null;
            for ($attempt = 1; $attempt <= 6; $attempt++) {
                try {
                    $response = Http::acceptJson()
                        ->timeout(30)
                        ->get($url, $query);
                } catch (\Throwable $e) {
                    $wait = min(10, 1 * $attempt);
                    $this->warn("net error: {$sportKey} (attempt {$attempt}) -> sleep {$wait}s");
                    sleep($wait);
                    continue;
                }

                if ($response->status() === 429) {
                    $retryAfter = (int) ($response->header('Retry-After') ?? 2);
                    $wait = max(1, min(30, $retryAfter));
                    $this->warn("429 rate limit: {$sportKey} -> sleep {$wait}s");
                    sleep($wait);
                    continue;
                }

                break;
            }

            if (!$response || !$response->successful()) {
                $status = $response ? $response->status() : 0;
                $this->error("fail: {$sportKey} (HTTP {$status})");
                $failed[] = ['sport_key' => $sportKey, 'status' => $status];
                continue;
            }

            // сохраняем "как есть" (raw JSON)
            File::put($filePath, $response->body());

            $remaining = $response->header('x-requests-remaining');
            $this->info("ok: {$sportKey} -> {$safeName}.json" . ($remaining !== null ? " (remaining: {$remaining})" : ''));

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        if ($failed) {
            $failPath = $dir . DIRECTORY_SEPARATOR . '_failed.json';
            File::put($failPath, json_encode($failed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn('Some requests failed. Saved: ' . $failPath);
            return self::FAILURE;
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
