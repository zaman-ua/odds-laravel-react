<?php

namespace App\Jobs;

use App\Models\Sport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class SyncOddsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * sport:
     *  - 'all' => диспатчим по одному job на каждый sport_key
     *  - конкретный sport_key => грузим файл и кладём кеш в Redis
     */
    public function __construct(public string $sport = 'all') {}

    public function handle(): void
    {
        // 1) Режим "all": разбиваем на отдельные job (рекомендую именно так)
        if ($this->sport === 'all') {
            Sport::query()
                ->whereNotNull('sport_key')
                ->where('sport_key', '<>', '')
                ->orderBy('sport_key')
                ->pluck('sport_key')
                ->each(function (string $sportKey) {
                    // отдельный job на каждый спорт
                    self::dispatch($sportKey)->onQueue($this->queue ?? 'default');
                });

            return;
        }

        // 2) Режим "один спорт"
        $sportKey = $this->sport;

        $dataKey = "odds:data:sport:{$sportKey}";

        // Всегда стартуем от json-файла (база)
        $payload = $this->buildFromFile($sportKey); // ['sport'=>..., 'items'=>...]
        $items = $payload['items'] ?? [];

        // --- необязательно, но как у тебя было: "шевелим" кэфы, не накапливая ---
        $eventChangeRate = 0.25; // 25% матчей за тик
        $cellChangeRate  = 0.60; // в выбранном матче 60% ячеек (1/X/2)

        foreach ($items as &$row) {
            if (!$this->chance($eventChangeRate)) {
                continue;
            }

            foreach (['odd_1', 'odd_x', 'odd_2'] as $k) {
                $baseOdd = $this->normOdd($row[$k] ?? null);
                if ($baseOdd === null) continue;

                if (!$this->chance($cellChangeRate)) {
                    continue;
                }

                $row[$k] = $this->mutateFromBase($baseOdd);
            }
        }
        unset($row);

        $payload['items'] = $items;
        // --- конец блока "шевеления" ---

        // 3) Пишем снимок в Redis + bump версии
        Redis::set($dataKey, json_encode($payload, JSON_UNESCAPED_UNICODE));
        Redis::set("odds:last_sync:sport:{$sportKey}", now()->toIso8601String());
        Redis::incr("odds:ver:sport:{$sportKey}");
    }

    private function buildFromFile(string $sportKey): array
    {
        $safe = preg_replace('~[^a-zA-Z0-9._-]+~', '_', $sportKey);
        $path = database_path("seeders/data/sports/{$safe}.json");

        if (!is_file($path)) {
            // если файла нет — всё равно кладём пустой payload, чтобы фронт не падал
            return [
                'sport' => $sportKey,
                'items' => [],
                'stale' => true,
                'error' => "file_not_found: {$path}",
            ];
        }

        $events = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $items = [];

        foreach (($events ?? []) as $ev) {
            $home = $ev['home_team'] ?? null;
            $away = $ev['away_team'] ?? null;

            $o1 = $ox = $o2 = null;

            // берём market key = h2h, первый попавшийся bookmaker (в файле ты уже ограничил betsson)
            foreach (($ev['bookmakers'] ?? []) as $bm) {
                foreach (($bm['markets'] ?? []) as $m) {
                    if (($m['key'] ?? null) !== 'h2h') continue;

                    foreach (($m['outcomes'] ?? []) as $out) {
                        $name  = $out['name'] ?? '';
                        $price = $out['price'] ?? null;

                        if ($name === 'Draw') $ox = $price;
                        elseif ($home !== null && $name === $home) $o1 = $price;
                        elseif ($away !== null && $name === $away) $o2 = $price;
                    }

                    break 2;
                }
            }

            $items[] = [
                'id'            => $ev['id'] ?? null,
                'sport_key'     => $ev['sport_key'] ?? $sportKey,
                'sport_title'   => $ev['sport_title'] ?? null,
                'commence_time' => $ev['commence_time'] ?? null,
                'home_team'     => $home,
                'away_team'     => $away,
                'odd_1'         => $o1,
                'odd_x'         => $ox,
                'odd_2'         => $o2,
            ];
        }

        usort($items, static fn($a, $b) => strcmp((string) $a['commence_time'], (string) $b['commence_time']));

        return [
            'sport' => $sportKey,
            'items' => $items,
            'stale' => false,
        ];
    }

    private function mutateFromBase(float $baseOdd): float
    {
        // множитель 1.00..1.34 (как у тебя было)
        $factor = random_int(100, 134) / 100;
        return round($baseOdd * $factor, 2);
    }

    private function normOdd(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        if (!is_numeric($v)) return null;
        return (float) $v;
    }

    private function chance(float $p): bool
    {
        $p = max(0.0, min(1.0, $p));
        return random_int(0, 1_000_000) <= (int) round($p * 1_000_000);
    }
}
