<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Helper;

/**
 * Submit contact-us requests to miniOrange (Privacy module).
 */
class SupportCurl
{
    private static function createAuthHeader(string $customerKey, string $apiKey): array
    {
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format((float) $currentTimestampInMillis, 0, '', '');

        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash('sha512', $stringToHash);

        return [
            'Content-Type: application/json',
            'Customer-Key: ' . $customerKey,
            'Timestamp: ' . $currentTimestampInMillis,
            'Authorization: ' . $authHeader,
        ];
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param list<string>|array<int, string> $headers
     */
    private static function callAPI(string $url, array $jsonData = [], array $headers = ['Content-Type: application/json']): string
    {
        $curl = new MoCurl();
        $options = [
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_AUTOREFERER' => true,
            'CURLOPT_TIMEOUT' => 0,
            'CURLOPT_MAXREDIRS' => 10,
        ];

        $data = in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)
            ? (!empty($jsonData) ? http_build_query($jsonData) : '')
            : (!empty($jsonData) ? (string) json_encode($jsonData) : '');

        $method = $data !== '' ? 'POST' : 'GET';
        $curl->setConfig($options);
        $curl->write($method, $url, '1.1', $headers, $data);
        $content = $curl->read();
        $curl->close();

        return is_string($content) ? $content : '';
    }

    public static function submitContactUs(string $email, string $phone, string $query): string
    {
        $url = SupportConstants::HOSTNAME . '/moas/rest/customer/contact-us';
        $query = '[' . SupportConstants::AREA_OF_INTEREST . ']: ' . $query;
        $customerKey = SupportConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = SupportConstants::DEFAULT_API_KEY;
        $fields = [
            'email' => $email,
            'phone' => $phone,
            'query' => $query,
            'ccEmail' => 'magentosupport@xecurify.com',
        ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);

        return self::callAPI($url, $fields, $authHeader);
    }

    // This function is used to sync the plugin metrics
    public static function sync_plugin_metrics($data)
    {
        $apiUrl = SupportConstants::MO_TRACKING_PORTAL_URL;
        $response = self::callAPI($apiUrl, $data);
        return $response;
    }
}
