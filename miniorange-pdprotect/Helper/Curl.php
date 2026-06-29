<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Helper;

/**
 * Lightweight cURL helper for MiniOrange PDProtect.
 *
 * Handles the contact-us API call (support form submission).
 * Authentication header algorithm mirrors MiniOrange_Okta.
 */
class Curl
{
    private const HOSTNAME             = 'https://login.xecurify.com';
    private const DEFAULT_CUSTOMER_KEY = '16555';
    private const DEFAULT_API_KEY      = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';
    private const AREA_OF_INTEREST     = 'Magento 2.0 Personal Data Protection';
    private const CC_EMAIL             = 'magentosupport@xecurify.com';

    /**
     * Submit a support / contact-us request to the miniOrange portal.
     *
     * @param  string $email Admin's contact email (required)
     * @param  string $phone Admin's phone number (optional)
     * @param  string $query The support query (required)
     * @return bool
     */
    public static function submit_contact_us(string $email, string $phone, string $query): bool
    {
        $url        = self::HOSTNAME . '/moas/rest/customer/contact-us';
        $query      = '[' . self::AREA_OF_INTEREST . ']: ' . $query;
        $authHeader = self::createAuthHeader(self::DEFAULT_CUSTOMER_KEY, self::DEFAULT_API_KEY);

        $fields = [
            'email'   => $email,
            'phone'   => $phone,
            'query'   => $query,
            'ccEmail' => self::CC_EMAIL,
        ];

        self::callApi($url, $fields, $authHeader);
        return true;
    }

    // ── Private helpers ───────────────────────────────────────

    /**
     * Build the miniOrange HMAC authentication header array.
     */
    private static function createAuthHeader(string $customerKey, string $apiKey): array
    {
        $timestamp    = (string) round(microtime(true) * 1000);
        $stringToHash = $customerKey . $timestamp . $apiKey;
        $authHash     = hash('sha512', $stringToHash);

        return [
            'Content-Type: application/json',
            "Customer-Key: {$customerKey}",
            "Timestamp: {$timestamp}",
            "Authorization: {$authHash}",
        ];
    }

    /**
     * Execute a cURL POST with JSON body.
     * Uses PHP's native cURL (no Magento DI) — this is a static utility class.
     */
    private static function callApi(string $url, array $data, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = (string) curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
