<?php
require 'db.php';
header('Content-Type: application/json');

function tableExists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    return ($res && $res->num_rows > 0);
}

function getColumns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    return $cols;
}

function pickFirstExisting(array $columns, array $candidates): ?string {
    $lower = array_map('strtolower', $columns);
    foreach ($candidates as $c) {
        $idx = array_search(strtolower($c), $lower, true);
        if ($idx !== false) {
            return $columns[$idx];
        }
    }
    return null;
}

function fetchSumSafe(mysqli $conn, string $sql): float {
    $st = $conn->prepare($sql);
    if (!$st) return 0.0;
    if (!$st->execute()) {
        $st->close();
        return 0.0;
    }
    $res = $st->get_result();
    $sum = $res ? (float)(($res->fetch_assoc())['sum'] ?? 0) : 0.0;
    $st->close();
    return $sum;
}

$salesTableCandidates = ['sales','sale','orders','order','transactions','transaction','invoice','invoices'];
$amountCandidates     = ['total','total_amount','grand_total','amount','paid','net_total','final_total'];
$dateCandidates       = ['created_at','date','sale_date','sold_at','datetime','timestamp','createdOn'];

$salesTable = null;
$amountCol  = null;
$dateCol    = null;

foreach ($salesTableCandidates as $t) {
    if (tableExists($conn, $t)) {
        $cols = getColumns($conn, $t);
        $a = pickFirstExisting($cols, $amountCandidates);
        $d = pickFirstExisting($cols, $dateCandidates);
        if ($a && $d) {
            $salesTable = $t;
            $amountCol = $a;
            $dateCol = $d;
            break;
        }
    }
}

$earnToday = 0.0;
$earnWeek  = 0.0;
$earnMonth = 0.0;
$earnYear  = 0.0;

if ($salesTable && $amountCol && $dateCol) {
    $tbl = "`" . str_replace("`", "", $salesTable) . "`";
    $amt = "`" . str_replace("`", "", $amountCol) . "`";
    $dt  = "`" . str_replace("`", "", $dateCol) . "`";

    $earnToday = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE DATE($dt)=CURDATE()");
    $earnWeek  = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEARWEEK($dt,1)=YEARWEEK(CURDATE(),1)");
    $earnMonth = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEAR($dt)=YEAR(CURDATE()) AND MONTH($dt)=MONTH(CURDATE())");
    $earnYear  = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEAR($dt)=YEAR(CURDATE())");
}

echo json_encode([
    'success' => true,
    'today'   => $earnToday,
    'week'    => $earnWeek,
    'month'   => $earnMonth,
    'year'    => $earnYear
]);