<?php
// api/social/youtube.php: public read-only feed of the school's YouTube channel.
//
// Reads the channel's public Atom feed (no API key, no OAuth), caches the parsed
// result on disk, and returns the newest uploads as JSON. Nothing is written to
// the database, so the landing page shows videos with zero admin work.
//
// GET /api/social/youtube.php?limit=3
// -> { "data": [ { id, title, url, published, thumbnail } ], "channel_url": "..." }

use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') Response::error('Method not allowed', 405);

const YT_CACHE_TTL = 21600;   // 6 hours; the channel posts a few times a month
const YT_HTTP_TIMEOUT = 6;    // never let a slow YouTube stall the landing page

$config     = app_config();
$channelId  = trim((string)($config['social']['youtube_channel_id'] ?? ''));
$channelUrl = trim((string)($config['social']['youtube_url'] ?? ''));
$limit      = max(1, min(12, (int)($_GET['limit'] ?? 3)));

if ($channelId === '') Response::json(['data' => [], 'channel_url' => $channelUrl]);

/**
 * Fetch a URL as a string, or null on any failure.
 * The Atom feed is public, but YouTube gates it on the User-Agent: a missing UA
 * returns 500 and a custom bot UA returns 404, so send a normal browser string.
 */
function yt_http_get(string $url): ?string {
    $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => YT_HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => YT_HTTP_TIMEOUT,
            CURLOPT_USERAGENT      => $ua,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // No curl_close(): it is a no-op since PHP 8.0 and deprecated in 8.5.
        return ($body !== false && $code === 200) ? (string)$body : null;
    }
    if (!ini_get('allow_url_fopen')) return null;
    $ctx = stream_context_create(['http' => [
        'timeout' => YT_HTTP_TIMEOUT,
        'header'  => "User-Agent: $ua\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? null : $body;
}

/** Parse the Atom feed into a plain list of videos. Returns null if unparseable. */
function yt_parse_feed(string $xml): ?array {
    $prev = libxml_use_internal_errors(true);
    $feed = simplexml_load_string($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if ($feed === false) return null;

    $out = [];
    foreach ($feed->entry as $entry) {
        $yt    = $entry->children('http://www.youtube.com/xml/schemas/2015');
        $media = $entry->children('http://search.yahoo.com/mrss/');
        $id    = trim((string)$yt->videoId);
        if ($id === '') continue;

        $thumb = '';
        if (isset($media->group->thumbnail)) {
            $thumb = (string)$media->group->thumbnail->attributes()['url'];
        }

        $out[] = [
            'id'        => $id,
            'title'     => trim((string)$entry->title),
            'url'       => 'https://www.youtube.com/watch?v=' . $id,
            'published' => trim((string)$entry->published),
            'thumbnail' => $thumb ?: 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
        ];
    }
    return $out;
}

$slug      = preg_replace('/[^A-Za-z0-9_-]/', '', $channelId);
$cacheDir  = dirname(__DIR__, 2) . '/tmp/social';
$cacheFile = $cacheDir . '/youtube-' . $slug . '.json';

// Serve from cache while it is fresh.
$cached = null;
if (is_readable($cacheFile)) {
    $raw = @file_get_contents($cacheFile);
    $decoded = $raw === false ? null : json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['videos']) && is_array($decoded['videos'])) {
        $cached = $decoded;
        if ((time() - (int)($decoded['fetched_at'] ?? 0)) < YT_CACHE_TTL) {
            Response::json([
                'data'        => array_slice($decoded['videos'], 0, $limit),
                'channel_url' => $channelUrl,
                'cached'      => true,
            ]);
        }
    }
}

$xml    = yt_http_get('https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($channelId));
$videos = $xml === null ? null : yt_parse_feed($xml);

if ($videos === null) {
    // YouTube unreachable or rate-limiting: fall back to stale cache rather than
    // blanking the section, and empty only if we have never had a good fetch.
    Response::json([
        'data'        => $cached ? array_slice($cached['videos'], 0, $limit) : [],
        'channel_url' => $channelUrl,
        'stale'       => (bool)$cached,
    ]);
}

if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents(
        $cacheFile,
        json_encode(['fetched_at' => time(), 'videos' => $videos]),
        LOCK_EX
    );
}

Response::json([
    'data'        => array_slice($videos, 0, $limit),
    'channel_url' => $channelUrl,
]);
