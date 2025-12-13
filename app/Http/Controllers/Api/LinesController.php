<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

final class LinesController
{
    public function __invoke(Request $request)
    {
        $sport = $request->query('sport', 'soccer');

        $version = (int) (Redis::get("odds:ver:sport:$sport") ?? 0);
        $etag = "\"lines-{$sport}-v{$version}\"";

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $json = Redis::get("odds:data:sport:$sport");

        if (!$json) {
            return response()->json([
                'sport' => $sport,
                'version' => $version,
                'items' => [],
                'stale' => true,
            ])->header('ETag', $etag);
        }

        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}
