<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
    exit;
}

// Waktu server
$timestamp = date("Y-m-d H:i:s");

// Tambahkan waktu ke data JSON (opsional)
$now = time();
$data['server_time'] = $timestamp;
$minute = (int)date("i", $now);

// Simpan ke log 15 menit jika waktunya tepat dan belum tercatat
if (in_array($minute, [0, 15, 30, 45])) {
    $logFile = __DIR__ . "/histori_15m.txt";

    // Ambil baris terakhir jika file ada
    $lastMinute = -1;
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLine = end($lines);
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}):(\d{2}):\d{2}/', $lastLine, $match)) {
            $lastMinute = (int)$match[2];
        }
    }

    // Jika menit saat ini belum ada, catat
    if ($minute !== $lastMinute) {
        file_put_contents($logFile, "$timestamp => $json" . PHP_EOL, FILE_APPEND);
    }
}


// Simpan ke cache untuk real-time (overwrite)
file_put_contents(__DIR__ . "/data_last.json", json_encode($data, JSON_PRETTY_PRINT));

http_response_code(200);
echo json_encode(["status" => "success", "message" => "Data disimpan", "timestamp" => $timestamp]);

