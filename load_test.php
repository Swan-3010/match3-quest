<?php
/**
 * Простой нагрузочный тест для Match Quest API
 * Запуск: php load_test.php
 * Требует: 10+ одновременных запусков в разных терминалах
 */

$baseUrl = $argv[1] ?? 'http://localhost:8000';
$concurrent = (int) ($argv[2] ?? 10);
$requests = (int) ($argv[3] ?? 100);

echo "Нагрузочный тест: {$concurrent} concurrent, {$requests} requests\n";
echo "URL: {$baseUrl}\n\n";

$results = ['success' => 0, 'fail' => 0, 'times' => []];

for ($i = 0; $i < $requests; $i++) {
    $start = microtime(true);

    $ch = curl_init("{$baseUrl}/api/levels");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = (microtime(true) - $start) * 1000;
    curl_close($ch);

    $results['times'][] = $time;
    if ($httpCode === 200 || $httpCode === 401) {
        $results['success']++;
    } else {
        $results['fail']++;
    }

    if ($i % 10 === 0) echo ".";
    usleep(100000); // 100ms delay between requests
}

echo "\n\nРезультаты:\n";
echo "Успешно: {$results['success']}\n";
echo "Ошибок: {$results['fail']}\n";
echo "Среднее время: " . round(array_sum($results['times']) / count($results['times']), 2) . " ms\n";
echo "Мин: " . round(min($results['times']), 2) . " ms\n";
echo "Макс: " . round(max($results['times']), 2) . " ms\n";
echo "95-й перцентиль: " . round(percentile($results['times'], 95), 2) . " ms\n";

function percentile(array $arr, float $p): float {
    sort($arr);
    $index = ($p / 100) * (count($arr) - 1);
    $lower = floor($index);
    $upper = ceil($index);
    $weight = $index - $lower;
    return $arr[$lower] * (1 - $weight) + $arr[$upper] * $weight;
}
