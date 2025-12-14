<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;

class LinesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $sports = Sport::query()
            ->whereNotNull('sport_key')
            ->where('sport_key', '<>', '')
            ->orderBy('group_name')
            ->orderBy('title')
            ->get();

        $sportsForUi = $sports->map(fn ($s) => [
            'key'   => $s->sport_key,
            'title' => $s->title,
            'group' => $s->group_name ?: 'Other',
        ]);

        $groups = $sportsForUi
            ->groupBy('group')
            ->map(fn ($items, $group) => [
                'group'  => $group,
                'sports' => $items->values()->all(),
            ])
        ->values()
        ->all();

        return view('lines.index', [
            'groups' => $groups,
        ]);
    }
}
