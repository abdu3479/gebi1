<?php
class EthioTelecomSms {
    private $apiUrl = "https://sms.ethiotelecom.et/api/send";
    private $username;
    private $password;
    private $senderId;
    private $maxRetries = 3;
    private $timeout = 30;

    public function __construct($username, $password, $senderId) {
        $this->username = $username;
        $this->password = $password;
        $this->senderId = $senderId;
    }

    public function sendSms($phoneNumber, $message, $retryCount = 0) {
        $validatedPhone = $this->validatePhoneNumber($phoneNumber);
        $truncatedMsg = $this->truncateMessage($message);

        $payload = [
            'username' => $this->username,
            'password' => $this->password,
            'to' => $validatedPhone,
            'text' => $truncatedMsg,
            'senderid' => $this->senderId
        ];

        try {
            $response = $this->makeApiRequest($payload);
            return $this->parseResponse($response, $validatedPhone, $truncatedMsg);
            
        } catch (Exception $e) {
            if ($retryCount < $this->maxRetries) {
                sleep(1); // Wait before retry
                return $this->sendSms($phoneNumber, $message, $retryCount + 1);
            }
            throw new Exception("Failed after {$this->maxRetries} attempts: " . $e->getMessage());
        }
    }

    private function validatePhoneNumber($phone) {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert local formats to international
        if (preg_match('/^0[79]\d{8}$/', $cleaned)) {
            return '+251' . substr($cleaned, 1);
        }
        
        if (preg_match('/^[79]\d{8}$/', $cleaned)) {
            return '+251' . $cleaned;
        }
        
        if (preg_match('/^251[79]\d{8}$/', $cleaned)) {
            return '+' . $cleaned;
        }
        
        if (!preg_match('/^\+251[79]\d{8}$/', $cleaned)) {
            throw new Exception("Invalid Ethiopian phone format: $phone");
        }
        
        return $cleaned;
    }

    private function truncateMessage($message) {
        return mb_substr($message, 0, 612, 'UTF-8');
    }

    private function makeApiRequest($payload) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => '/path/to/cacert.pem', // For production
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL error: $error");
        }

        return [
            'status_code' => $httpCode,
            'body' => $response
        ];
    }

    private function parseResponse($response, $phone, $message) {
        $httpCode = $response['status_code'];
        $body = $response['body'];
        
        $result = [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'phone' => $phone,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'raw_response' => $body
        ];

        // Try to parse JSON response
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $result['api_response'] = $json;
            
            // Adapt these based on Ethio Telecom's actual response format
            if (isset($json['status'])) {
                $result['success'] = ($json['status'] === 'success');
                $result['message_id'] = $json['message_id'] ?? null;
            }
        }

        if (!$result['success']) {
            throw new Exception("SMS delivery failed. HTTP $httpCode. Response: $body");
        }

        return $result;
    }

    public function getBalance() {
        // Implement when Ethio Telecom provides balance check API
        throw new Exception("Balance check not implemented");
    }
}
?>