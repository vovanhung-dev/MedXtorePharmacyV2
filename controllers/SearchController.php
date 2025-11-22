<?php
require_once __DIR__ . '/../models/Product.php';

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = $_GET['term'];
$product = new Product();
$results = $product->searchSuggestions($term); // Gọi model

// Trả về mảng tên thuốc
$suggestions = array_map(function ($item) {
    return $item['ten_thuoc'];
}, $results);

echo json_encode($suggestions);
?>