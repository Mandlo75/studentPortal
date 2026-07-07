<?php
$body = file_get_contents('http://196.220.119.61:8080/testfinancials.php?id=N02423145F');
$jsonStart = strpos($body, '{');
$jsonCandidate = substr($body, $jsonStart);
$decoded = json_decode($jsonCandidate, true);
$transactions = $decoded['data']['transactions'] ?? $decoded['transactions'] ?? $decoded;
$records = $transactions['statement_records'] ?? [];
$totalBilledUsd = 0.0;
$totalPaidUsd = 0.0;
foreach ($records as $r) {
    $r = array_change_key_case($r, CASE_LOWER);
    $ex = isset($r['exchangerate']) && floatval($r['exchangerate']) > 0 ? floatval($r['exchangerate']) : null;
    if (! empty($r['foreigndebit']) && floatval($r['foreigndebit']) > 0) {
        $totalBilledUsd += floatval($r['foreigndebit']);
    } elseif (! empty($r['debit']) && floatval($r['debit']) > 0 && $ex) {
        $totalBilledUsd += floatval($r['debit']) / $ex;
    }
    if (isset($r['foreigncredit']) && floatval($r['foreigncredit']) < 0) {
        $totalPaidUsd += abs(floatval($r['foreigncredit']));
    } elseif (isset($r['credit']) && floatval($r['credit']) < 0 && $ex) {
        $totalPaidUsd += abs(floatval($r['credit'])) / $ex;
    }
}

echo "Total billed USD: $" . number_format($totalBilledUsd, 2) . "\n";
echo "Total paid USD: $" . number_format($totalPaidUsd, 2) . "\n";
