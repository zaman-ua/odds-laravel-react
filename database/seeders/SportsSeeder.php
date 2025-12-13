<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class SportsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/sports.json');

        if (!is_file($path)) {
            throw new \RuntimeException("sports.json not found: {$path}");
        }

        $items = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $now = Carbon::now();

        $rows = array_map(static function (array $it) use ($now) {
            return [
                'sport_key'     => $it['key'],
                'group_name'    => $it['group'],
                'title'         => $it['title'],
                'description'   => $it['description'] ?? null,
                'active'        => (bool)($it['active'] ?? false),
                'has_outrights' => (bool)($it['has_outrights'] ?? false),
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }, $items);

        // upsert: если sport_key уже есть — обновим поля
        Sport::upsert(
            $rows,
            ['sport_key'],
            ['group_name', 'title', 'description', 'active', 'has_outrights', 'updated_at']
        );
    }
}
