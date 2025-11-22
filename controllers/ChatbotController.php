<?php
$data = json_decode(file_get_contents("php://input"));
$question = $data->message ?? "";

// Gửi câu hỏi lên Gemini
$response = callGemini($question);
echo json_encode(['reply' => $response]);

function callGemini($prompt) {
    $api_key = "AIzaSyAUIcbhSWWsfosPHI8M6UW1sdip90ShE78"; // Thay bằng API key thật
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=$api_key";

    $payload = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return "Lỗi CURL: $error";
    }

    if ($httpcode !== 200) {
        return "Lỗi HTTP $httpcode: $result";
    }

    // Debugging: In ra kết quả phản hồi từ API để kiểm tra
    error_log('API Response: ' . $result);

    $json = json_decode($result, true);
    return $json['candidates'][0]['content']['parts'][0]['text'] ?? "Không có phản hồi.";
}

