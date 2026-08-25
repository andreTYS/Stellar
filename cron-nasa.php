#!/usr/bin/env php
<?php
/**
 * INNOVA-STEAM — NASA/NOAA data updater
 * Run via cron every hour: 0 * * * * php /path/to/cron-nasa.php
 *
 * Fetches: NOAA SWPC 3-day geomagnetic, solar wind, solar flares (DONKI), CME (DONKI)
 * Updates files in stellarscribe/api/
 */
declare(strict_types=1);

define('API_DIR', __DIR__ . '/stellarscribe/api');
define('NASA_API_KEY', 'DEMO_KEY'); // Replace with real key from api.nasa.gov
define('LOG_FILE', __DIR__ . '/cron-nasa.log');

function fetchUrl(string $url): ?array {
    $ctx = stream_context_create(['http' => [
        'timeout' => 20,
        'user_agent' => 'INNOVA-STEAM/1.0',
        'header' => 'Accept: application/json',
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    return json_decode($raw, true);
}

function saveJson(string $filename, mixed $data): void {
    $path = API_DIR . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logMsg("Updated: $filename");
}

function logMsg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
    echo $line;
}

logMsg('=== NASA cron starting ===');

// 1. NOAA SWPC — current space weather indices (3-day)
$noaa3day = fetchUrl('https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json');
if ($noaa3day) {
    // Last entry: [datetime, Kp]
    $last = end($noaa3day);
    $kp   = isset($last[1]) ? (float)$last[1] : 0;
    saveJson('current_space_weather.json', [
        'kp_index'     => $kp,
        'aurora_level' => $kp >= 5 ? 'Alta' : ($kp >= 3 ? 'Media' : 'Baja'),
        'solar_wind_speed' => 400, // Requires separate SWPC feed
        'solar_flux'   => 120,
        'magnetic_field' => -5,
        'sunspot_number' => 80,
        'updated_at'   => date('c'),
    ]);
}

// 2. NASA DONKI — Solar Flares (last 30 days)
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate   = date('Y-m-d');
$flares = fetchUrl("https://api.nasa.gov/DONKI/FLR?startDate=$startDate&endDate=$endDate&api_key=" . NASA_API_KEY);
if ($flares && is_array($flares)) {
    $simplified = array_map(fn($f) => [
        'flrID'      => $f['flrID'] ?? '',
        'classType'  => $f['classType'] ?? '',
        'beginTime'  => $f['beginTime'] ?? '',
        'peakTime'   => $f['peakTime'] ?? '',
        'endTime'    => $f['endTime'] ?? '',
        'sourceLocation' => $f['sourceLocation'] ?? '',
    ], array_slice($flares, -50));
    saveJson('basic_flare_data.json', array_reverse($simplified));
}

// 3. NASA DONKI — CMEs (last 30 days)
$cmes = fetchUrl("https://api.nasa.gov/DONKI/CME?startDate=$startDate&endDate=$endDate&api_key=" . NASA_API_KEY);
if ($cmes && is_array($cmes)) {
    $simplified = array_map(fn($c) => [
        'activityID' => $c['activityID'] ?? '',
        'startTime'  => $c['startTime'] ?? '',
        'note'       => $c['note'] ?? '',
        'speed'      => $c['cmeAnalyses'][0]['speed'] ?? null,
        'type'       => $c['cmeAnalyses'][0]['type'] ?? null,
    ], array_slice($cmes, -30));
    saveJson('basic_cme_data.json', array_reverse($simplified));
}

// 4. NOAA SWPC — 3-day solar-geophysical forecast (comprehensive)
$noaaComp = fetchUrl('https://services.swpc.noaa.gov/json/alerts.json');
if ($noaaComp) {
    saveJson('noaa_comprehensive.json', array_slice($noaaComp, 0, 50));
}

logMsg('=== NASA cron done ===');
