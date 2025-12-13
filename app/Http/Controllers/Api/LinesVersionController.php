<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

final class LinesVersionController
{
    public function __invoke(Request $request)
    {
        $sport = $request->query('sport', 'soccer');

        $version = (int) (Redis::get("odds:ver:sport:$sport") ?? 0);
        $etag = "\"v{$version}\"";

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, max-age=0, must-revalidate');
        }

        return response()->json([
            'sport' => $sport,
            'version' => $version,
            'last_sync' => Redis::get("odds:last_sync:sport:$sport"),
        ])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}
