<?php
require_once __DIR__ . '/../config/database.php';

class PrescriptionScanner {
    private $conn;
    private $apiKey;
    private $apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->apiKey = $this->loadEnvVariable('GEMINI_API_KEY');
    }

    private function loadEnvVariable($key) {
        if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
            return $_ENV[$key];
        }

        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === $key) {
                        return $value;
                    }
                }
            }
        }
        return '';
    }

    public function scanPrescription($file) {
        try {
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            $fileContent = file_get_contents($file['tmp_name']);
            $base64Data = base64_encode($fileContent);
            $mimeType = $file['type'];

            if ($mimeType === 'application/pdf') {
                return $this->scanPdfPrescription($file);
            }

            $extractedData = $this->analyzeWithGemini($base64Data, $mimeType);

            if (!$extractedData['success']) {
                return $extractedData;
            }

            $matchedMedicines = $this->matchMedicinesWithDatabase($extractedData['medicines']);

            return [
                'success' => true,
                'message' => 'Đã phân tích đơn thuốc thành công',
                'raw_text' => $extractedData['raw_text'] ?? '',
                'medicines' => $matchedMedicines,
                'patient_info' => $extractedData['patient_info'] ?? null,
                'doctor_info' => $extractedData['doctor_info'] ?? null,
                'diagnosis' => $extractedData['diagnosis'] ?? null,
                'date' => $extractedData['date'] ?? null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xử lý đơn thuốc: ' . $e->getMessage()
            ];
        }
    }

    private function validateFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'File không hợp lệ'];
        }

        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP) hoặc PDF'];
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File không được vượt quá 10MB'];
        }

        return ['success' => true];
    }

    private function analyzeWithGemini($base64Data, $mimeType) {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'Chưa cấu hình API key cho Gemini AI'];
        }

        $prompt = $this->buildPrompt();

        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 4096,
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiEndpoint . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Lỗi kết nối API: ' . $error];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'HTTP Error ' . $httpCode;
            return ['success' => false, 'message' => 'Lỗi API: ' . $errorMessage];
        }

        $result = json_decode($response, true);

        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return ['success' => false, 'message' => 'Không thể đọc nội dung đơn thuốc'];
        }

        $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'];

        return $this->parseAIResponse($aiResponse);
    }

    private function buildPrompt() {
        return "Bạn là một trợ lý AI chuyên đọc và phân tích đơn thuốc. Hãy phân tích hình ảnh đơn thuốc này và trích xuất thông tin theo định dạng JSON sau:

{
    \"patient_info\": {
        \"name\": \"Tên bệnh nhân (nếu có)\",
        \"age\": \"Tuổi (nếu có)\",
        \"gender\": \"Giới tính (nếu có)\",
        \"address\": \"Địa chỉ (nếu có)\"
    },
    \"doctor_info\": {
        \"name\": \"Tên bác sĩ (nếu có)\",
        \"hospital\": \"Bệnh viện/Phòng khám (nếu có)\"
    },
    \"diagnosis\": \"Chẩn đoán (nếu có)\",
    \"date\": \"Ngày kê đơn (nếu có)\",
    \"medicines\": [
        {
            \"name\": \"Tên thuốc (viết đầy đủ, chuẩn hóa)\",
            \"dosage\": \"Hàm lượng (ví dụ: 500mg, 10mg)\",
            \"quantity\": \"Số lượng (số nguyên)\",
            \"unit\": \"Đơn vị (viên, vỉ, hộp, chai, tuýp...)\",
            \"usage\": \"Cách dùng (ví dụ: Ngày 2 lần, mỗi lần 1 viên)\",
            \"notes\": \"Ghi chú thêm (nếu có)\"
        }
    ],
    \"raw_text\": \"Toàn bộ nội dung text đọc được từ đơn thuốc\"
}

Lưu ý quan trọng:
1. Chỉ trả về JSON, không có text thừa
2. Tên thuốc cần được chuẩn hóa (viết hoa chữ cái đầu, bỏ ký tự thừa)
3. Số lượng phải là số nguyên
4. Nếu không đọc được thông tin nào, để giá trị null
5. Nếu đơn thuốc viết tay khó đọc, cố gắng đoán dựa trên ngữ cảnh y khoa
6. Với các tên thuốc viết tắt, hãy viết đầy đủ tên gốc

Hãy phân tích hình ảnh đơn thuốc:";
    }

    private function parseAIResponse($aiResponse) {
        $cleanResponse = $aiResponse;
        $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
        $cleanResponse = preg_replace('/```\s*/', '', $cleanResponse);
        $cleanResponse = trim($cleanResponse);

        $data = json_decode($cleanResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            preg_match('/\{[\s\S]*\}/', $cleanResponse, $matches);
            if (!empty($matches[0])) {
                $data = json_decode($matches[0], true);
            }
        }

        if (!$data || !isset($data['medicines'])) {
            return [
                'success' => false,
                'message' => 'Không thể phân tích nội dung đơn thuốc. Vui lòng chụp ảnh rõ hơn.',
                'raw_response' => $aiResponse
            ];
        }

        return [
            'success' => true,
            'medicines' => $data['medicines'] ?? [],
            'patient_info' => $data['patient_info'] ?? null,
            'doctor_info' => $data['doctor_info'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? null,
            'date' => $data['date'] ?? null,
            'raw_text' => $data['raw_text'] ?? ''
        ];
    }

    private function scanPdfPrescription($file) {
        if (!extension_loaded('imagick')) {
            $fileContent = file_get_contents($file['tmp_name']);
            $base64Data = base64_encode($fileContent);
            return $this->analyzeWithGemini($base64Data, 'application/pdf');
        }

        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($file['tmp_name'] . '[0]');
            $imagick->setImageFormat('png');

            $imageData = $imagick->getImageBlob();
            $base64Data = base64_encode($imageData);

            $imagick->destroy();

            return $this->analyzeWithGemini($base64Data, 'image/png');
        } catch (Exception $e) {
            $fileContent = file_get_contents($file['tmp_name']);
            $base64Data = base64_encode($fileContent);
            return $this->analyzeWithGemini($base64Data, 'application/pdf');
        }
    }

    private function matchMedicinesWithDatabase($medicines) {
        $matchedMedicines = [];

        foreach ($medicines as $medicine) {
            $medicineName = $medicine['name'] ?? '';
            $dosage = $medicine['dosage'] ?? '';
            $quantity = intval($medicine['quantity'] ?? 1);

            if (empty($medicineName)) {
                continue;
            }

            $products = $this->searchProducts($medicineName, $dosage);

            $matchedMedicines[] = [
                'original' => $medicine,
                'name' => $medicineName,
                'dosage' => $dosage,
                'quantity' => $quantity,
                'unit' => $medicine['unit'] ?? 'viên',
                'usage' => $medicine['usage'] ?? '',
                'notes' => $medicine['notes'] ?? '',
                'matched_products' => $products,
                'best_match' => !empty($products) ? $products[0] : null,
                'match_status' => !empty($products) ? 'found' : 'not_found'
            ];
        }

        return $matchedMedicines;
    }

    private function searchProducts($name, $dosage = '') {
        $searchName = $this->normalizeSearchTerm($name);
        $searchDosage = $this->normalizeSearchTerm($dosage);

        error_log("=== PrescriptionScanner searchProducts ===");
        error_log("Original name: " . $name);
        error_log("Normalized searchName: " . $searchName);
        error_log("Original dosage: " . $dosage);
        error_log("Normalized searchDosage: " . $searchDosage);

        // Use LOWER() for case-insensitive search on both sides
        $query = "SELECT
                    t.id,
                    t.ten_thuoc,
                    t.hoatchat,
                    t.hamluong,
                    t.hinhanh,
                    t.mota,
                    l.ten_loai,
                    COALESCE(SUM(k.soluong), 0) as ton_kho,
                    MIN(k.gia) as gia_nhap,
                    t.gia as gia_ban,
                    dv.ten_donvi
                  FROM thuoc t
                  LEFT JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
                  LEFT JOIN donvi dv ON t.donvi_id = dv.id
                  WHERE (
                      LOWER(t.ten_thuoc) LIKE LOWER(?)
                      OR LOWER(t.ten_thuoc) LIKE LOWER(?)
                      OR LOWER(t.hoatchat) LIKE LOWER(?)
                  )";

        $params = [
            '%' . $searchName . '%',
            $searchName . '%',
            '%' . $searchName . '%'
        ];

        if (!empty($searchDosage)) {
            $query .= " AND (LOWER(t.hamluong) LIKE LOWER(?) OR LOWER(t.ten_thuoc) LIKE LOWER(?))";
            $params[] = '%' . $searchDosage . '%';
            $params[] = '%' . $searchDosage . '%';
        }

        $query .= " GROUP BY t.id, t.ten_thuoc, t.hoatchat, t.hamluong, t.hinhanh, t.mota, l.ten_loai, t.gia, dv.ten_donvi
                    ORDER BY
                        CASE
                            WHEN LOWER(t.ten_thuoc) LIKE LOWER(?) THEN 1
                            WHEN LOWER(t.ten_thuoc) LIKE LOWER(?) THEN 2
                            ELSE 3
                        END,
                        ton_kho DESC
                    LIMIT 5";

        $params[] = $searchName . '%';
        $params[] = '%' . $searchName . '%';

        try {
            error_log("PrescriptionScanner SQL Query: " . $query);
            error_log("PrescriptionScanner Params: " . json_encode($params, JSON_UNESCAPED_UNICODE));

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("PrescriptionScanner Found " . count($products) . " products");
            if (count($products) > 0) {
                error_log("First product: " . json_encode($products[0], JSON_UNESCAPED_UNICODE));
            }

            foreach ($products as &$product) {
                $product['match_score'] = $this->calculateMatchScore($name, $dosage, $product);
                $product['ton_kho'] = intval($product['ton_kho']);
                $product['gia_ban'] = floatval($product['gia_ban']);
            }

            usort($products, function($a, $b) {
                return $b['match_score'] - $a['match_score'];
            });

            return $products;
        } catch (Exception $e) {
            error_log('Search products error: ' . $e->getMessage());
            error_log('Search products trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    private function normalizeSearchTerm($term) {
        $term = preg_replace('/\s+/', ' ', trim($term));
        $term = mb_strtolower($term, 'UTF-8');
        $term = preg_replace('/\s*(viên|vỉ|hộp|chai|tuýp|gói|ống)\s*$/i', '', $term);
        return $term;
    }

    private function calculateMatchScore($prescriptionName, $prescriptionDosage, $product) {
        $score = 0;

        $productName = mb_strtolower($product['ten_thuoc'], 'UTF-8');
        $productDosage = mb_strtolower($product['hamluong'] ?? '', 'UTF-8');
        $productIngredient = mb_strtolower($product['hoatchat'] ?? '', 'UTF-8');

        $searchName = mb_strtolower($prescriptionName, 'UTF-8');
        $searchDosage = mb_strtolower($prescriptionDosage, 'UTF-8');

        if ($productName === $searchName) {
            $score += 100;
        } elseif (strpos($productName, $searchName) === 0) {
            $score += 80;
        } elseif (strpos($productName, $searchName) !== false) {
            $score += 60;
        } elseif (strpos($productIngredient, $searchName) !== false) {
            $score += 50;
        }

        if (!empty($searchDosage) && !empty($productDosage)) {
            if (strpos($productDosage, $searchDosage) !== false ||
                strpos($productName, $searchDosage) !== false) {
                $score += 30;
            }
        }

        if ($product['ton_kho'] > 0) {
            $score += 10;
        }

        return $score;
    }

    public function saveScanHistory($data, $userId = null) {
        try {
            $query = "INSERT INTO prescription_scan_history
                      (user_id, scan_data, medicines_count, created_at)
                      VALUES (?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $userId,
                json_encode($data, JSON_UNESCAPED_UNICODE),
                count($data['medicines'] ?? [])
            ]);

            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            error_log('Save scan history error: ' . $e->getMessage());
            return false;
        }
    }
}
