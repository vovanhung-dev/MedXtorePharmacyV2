<?php
/**
 * Test search endpoint để debug
 * Truy cập: http://localhost:8000/api/pos/test-search.php?q=Deka%20Plus
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

$searchTerm = $_GET['q'] ?? 'Deka Plus';

echo "<pre>";
echo "=== TEST SEARCH: $searchTerm ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Test 1: Direct search
    echo "1. Direct search (ten_thuoc LIKE '%$searchTerm%'):\n";
    $stmt = $conn->prepare("SELECT id, ten_thuoc, hoatchat FROM thuoc WHERE ten_thuoc LIKE ?");
    $stmt->execute(['%' . $searchTerm . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found: " . count($results) . " results\n";
    foreach ($results as $r) {
        echo "  - [{$r['id']}] {$r['ten_thuoc']} | {$r['hoatchat']}\n";
    }

    // Test 2: Case-insensitive search
    echo "\n2. Case-insensitive search (LOWER):\n";
    $searchLower = mb_strtolower($searchTerm, 'UTF-8');
    $stmt = $conn->prepare("SELECT id, ten_thuoc, hoatchat FROM thuoc WHERE LOWER(ten_thuoc) LIKE LOWER(?)");
    $stmt->execute(['%' . $searchLower . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found: " . count($results) . " results\n";
    foreach ($results as $r) {
        echo "  - [{$r['id']}] {$r['ten_thuoc']} | {$r['hoatchat']}\n";
    }

    // Test 3: List all products with similar names
    echo "\n3. All products containing 'deka' (case-insensitive):\n";
    $stmt = $conn->prepare("SELECT id, ten_thuoc, hoatchat FROM thuoc WHERE LOWER(ten_thuoc) LIKE '%deka%'");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found: " . count($results) . " results\n";
    foreach ($results as $r) {
        echo "  - [{$r['id']}] {$r['ten_thuoc']} | {$r['hoatchat']}\n";
    }

    // Test 4: Check exact product name in database
    echo "\n4. First 20 products in database:\n";
    $stmt = $conn->query("SELECT id, ten_thuoc FROM thuoc ORDER BY ten_thuoc ASC LIMIT 20");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $r) {
        echo "  - [{$r['id']}] {$r['ten_thuoc']}\n";
    }

    // Test 5: Search with stock
    echo "\n5. Search with stock info (full query):\n";
    $query = "SELECT
                t.id,
                t.ten_thuoc,
                t.hoatchat,
                t.gia as gia_ban,
                COALESCE(SUM(k.soluong), 0) as ton_kho
              FROM thuoc t
              LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
              WHERE LOWER(t.ten_thuoc) LIKE LOWER(?)
              GROUP BY t.id, t.ten_thuoc, t.hoatchat, t.gia
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute(['%' . $searchTerm . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found: " . count($results) . " results\n";
    foreach ($results as $r) {
        echo "  - [{$r['id']}] {$r['ten_thuoc']} | Stock: {$r['ton_kho']} | Price: {$r['gia_ban']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
