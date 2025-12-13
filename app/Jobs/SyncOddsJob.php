<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class SyncOddsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $sport = 'all') {}

    public function handle(): void
    {
        $sport = $this->sport; // 'all'
        $dataKey = "odds:data:sport:{$sport}";

        // 1) ВСЕГДА стартуем от json (база)
        $payload = $this->buildFromFile(); // ['sport'=>'all','items'=>...]
        $items = $payload['items'] ?? [];

        // 2) Настройки "сколько менять"
        $eventChangeRate = 0.25; // 25% матчей за тик
        $cellChangeRate  = 0.60; // в выбранном матче 60% ячеек (1/X/2)

        foreach ($items as &$row) {
            if (!$this->chance($eventChangeRate)) {
                continue; // матч не трогаем -> остаётся как в json
            }

            foreach (['odd_1', 'odd_x', 'odd_2'] as $k) {
                $baseOdd = $this->normOdd($row[$k] ?? null);
                if ($baseOdd === null) continue;

                if (!$this->chance($cellChangeRate)) {
                    continue; // эту ячейку не трогаем -> остаётся как в json
                }

                $row[$k] = $this->mutateFromBase($baseOdd);
            }
        }
        unset($row);

        $payload['items'] = $items;

        // 3) Пишем снимок в Redis + bump версии
        Redis::set($dataKey, json_encode($payload, JSON_UNESCAPED_UNICODE));
        Redis::set("odds:last_sync:sport:{$sport}", now()->toIso8601String());
        Redis::incr("odds:ver:sport:{$sport}");
    }

    private function mutateFromBase(float $baseOdd): float
    {
        // множитель 1.00..1.34 (только рост, но без накопления)
        $factor = random_int(100, 134) / 100;
        return round($baseOdd * $factor, 2);
    }

    private function normOdd(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        if (!is_numeric($v)) return null;
        return (float)$v;
    }

    private function chance(float $p): bool
    {
        $p = max(0.0, min(1.0, $p));
        return random_int(0, 1_000_000) <= (int) round($p * 1_000_000);
    }


    private function mutateOdd(float $odd): float
    {
        // множитель 1.00 - 1.34
        $factor = random_int(100, 134) / 100;

        // округлим до 2 знаков, чтобы красиво
        return round($odd * $factor, 2);
    }

    private function buildFromFile(): array
    {
        $path = database_path('seeders/data/odds.json');
        if (!is_file($path)) {
            throw new \RuntimeException("odds.json not found: {$path}");
        }

        $events = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $items = [];

        foreach ($events as $ev) {
            $home = $ev['home_team'] ?? null;
            $away = $ev['away_team'] ?? null;

            $o1 = $ox = $o2 = null;

            // market key = h2h, первый попавшийся букмекер
            foreach (($ev['bookmakers'] ?? []) as $bm) {
                foreach (($bm['markets'] ?? []) as $m) {
                    if (($m['key'] ?? null) !== 'h2h') continue;

                    foreach (($m['outcomes'] ?? []) as $out) {
                        $name = $out['name'] ?? '';
                        $price = $out['price'] ?? null;

                        if ($name === 'Draw') $ox = $price;
                        elseif ($name === $home) $o1 = $price;
                        elseif ($name === $away) $o2 = $price;
                    }

                    break 2;
                }
            }

            $items[] = [
                'id'            => $ev['id'] ?? null,
                'sport_key'     => $ev['sport_key'] ?? null,
                'sport_title'   => $ev['sport_title'] ?? null,
                'commence_time' => $ev['commence_time'] ?? null,
                'home_team'     => $home,
                'away_team'     => $away,
                'odd_1'         => $o1,
                'odd_x'         => $ox,
                'odd_2'         => $o2,
            ];
        }

        usort($items, static fn($a, $b) => strcmp((string)$a['commence_time'], (string)$b['commence_time']));

        return [
            'sport' => 'all',
            'items' => $items,
        ];
    }
}
