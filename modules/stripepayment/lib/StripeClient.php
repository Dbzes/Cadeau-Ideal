<?php
/**
 * Client Stripe minimal via HTTP direct — pas de SDK externe.
 * Couvre : PaymentIntents create/retrieve, Refunds create, vérification signature webhook.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeClient
{
    const API_BASE = 'https://api.stripe.com/v1';
    const API_VERSION = '2024-06-20';

    private $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function createPaymentIntent(array $params)
    {
        return $this->request('POST', '/payment_intents', $params);
    }

    public function retrievePaymentIntent($id)
    {
        return $this->request('GET', '/payment_intents/' . urlencode($id));
    }

    public function createRefund(array $params)
    {
        return $this->request('POST', '/refunds', $params);
    }

    public function listRefunds($paymentIntentId)
    {
        return $this->request('GET', '/refunds?payment_intent=' . urlencode($paymentIntentId) . '&limit=100');
    }

    /**
     * Vérifie la signature d'un webhook Stripe.
     * @throws Exception si invalide
     */
    public static function verifyWebhookSignature($payload, $sigHeader, $secret, $tolerance = 300)
    {
        if (empty($sigHeader) || empty($secret)) {
            throw new Exception('Missing signature or secret');
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $sigHeader) as $item) {
            $parts = explode('=', $item, 2);
            if (count($parts) !== 2) { continue; }
            if ($parts[0] === 't') {
                $timestamp = (int) $parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }
        if ($timestamp === null || empty($signatures)) {
            throw new Exception('Invalid signature format');
        }
        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        $match = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) { $match = true; break; }
        }
        if (!$match) {
            throw new Exception('Signature mismatch');
        }
        if (abs(time() - $timestamp) > $tolerance) {
            throw new Exception('Timestamp out of tolerance');
        }
        return true;
    }

    private function request($method, $path, array $params = [])
    {
        $url = self::API_BASE . $path;
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Stripe-Version: ' . self::API_VERSION,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
            $body = $this->buildFormBody($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Stripe HTTP error: ' . $curlErr);
        }
        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Stripe API error HTTP ' . $httpCode;
            throw new Exception($msg);
        }
        return $decoded;
    }

    /**
     * Aplatit un tableau en syntaxe form-urlencoded Stripe (ex: metadata[key]=value).
     */
    private function buildFormBody(array $params, $prefix = '')
    {
        $parts = [];
        foreach ($params as $k => $v) {
            $key = $prefix ? $prefix . '[' . $k . ']' : $k;
            if (is_array($v)) {
                $parts[] = $this->buildFormBody($v, $key);
            } elseif (is_bool($v)) {
                $parts[] = urlencode($key) . '=' . ($v ? 'true' : 'false');
            } elseif ($v === null) {
                continue;
            } else {
                $parts[] = urlencode($key) . '=' . urlencode((string) $v);
            }
        }
        return implode('&', $parts);
    }
}
