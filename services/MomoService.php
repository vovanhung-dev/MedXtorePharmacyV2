<?php

class MomoService {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $momoApiUrl;
    private $returnUrl;
    private $notifyUrl;
    private $requestType;

    public function __construct($config) {
        $this->partnerCode = $config['partnerCode'];
        $this->accessKey = $config['accessKey'];
        $this->secretKey = $config['secretKey'];
        $this->momoApiUrl = $config['momoApiUrl'];
        $this->returnUrl = $config['returnUrl'];
        $this->notifyUrl = $config['notifyUrl'];
        $this->requestType = $config['requestType'];
    }

    public function createPaymentUrl($orderId, $amount, $orderInfo) {
        // Validate order ID
        if (!preg_match('/^\d+$/', $orderId)) {
            throw new Exception("OrderId không hợp lệ. Chỉ được phép chứa số.");
        }

        // Chuyển đổi amount sang đơn vị xu (nhân với 100)
        $amountInCents = (int)($amount );

        // Tạo requestId ngẫu nhiên
        $requestId = time() . "";
        
        // Prepare request data
        $requestData = array(
            'partnerCode' => $this->partnerCode,
            'requestId' => $requestId,
            'amount' => (string)$amountInCents,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->returnUrl,
            'ipnUrl' => $this->notifyUrl,
            'requestType' => $this->requestType,
            'extraData' => '',
            'lang' => 'vi'
        );

        // Tạo chuỗi raw để tính signature
        $rawHash = "accessKey=" . $this->accessKey .
                  "&amount=" . $amountInCents .
                  "&extraData=" .
                  "&ipnUrl=" . $this->notifyUrl .
                  "&orderId=" . $orderId .
                  "&orderInfo=" . $orderInfo .
                  "&partnerCode=" . $this->partnerCode .
                  "&redirectUrl=" . $this->returnUrl .
                  "&requestId=" . $requestId .
                  "&requestType=" . $this->requestType;

        // Calculate signature
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        $requestData['signature'] = $signature;

        // Send request to Momo API
        $ch = curl_init($this->momoApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($requestData))
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Request to Momo API failed. Status: " . $httpCode . ". Response: " . $response);
        }

        $result = json_decode($response, true);
        
        if (!isset($result['payUrl'])) {
            throw new Exception("Invalid response from Momo API: " . $response);
        }

        return $result;
    }

    public function paymentExecute($requestData) {
        $amount = $requestData['amount'] ?? null;
        $orderInfo = $requestData['orderInfo'] ?? '';
        $orderId = $requestData['orderId'] ?? '';
        $message = $requestData['message'] ?? '';
        $errorCode = $requestData['errorCode'] ?? '';

        $isSuccess = $errorCode === "0";

        // Chuyển đổi amount từ xu sang đồng (chia cho 100)
        if ($amount !== null) {
            $amount = (float)$amount / 100;
        }

        return [
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'message' => $message,
            'errorCode' => $errorCode,
            'isSuccess' => $isSuccess
        ];
    }

    private function computeHmacSha256($message, $secretKey) {
        return hash_hmac('sha256', $message, $secretKey);
    }
} 