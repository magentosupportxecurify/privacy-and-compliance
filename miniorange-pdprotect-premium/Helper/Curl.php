<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Helper;

use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * HTTP client for MiniOrange account and plugin-portal API calls.
 *
 * Mirrors SSO's Curl.php method set — adapted for PDProtect's instance-based DI pattern
 * (vs SSO's static pattern) and using Magento's HTTP client.
 *
 * Auth header convention (mirrors SSO Curl::createAuthHeader):
 *   - Account management (register / login / check / forgot): DEFAULT credentials (shared app key)
 *   - License management (ccl / vml / mius):                  CUSTOMER credentials (stored after login)
 *
 * IMPORTANT: A fresh CurlFactory::create() instance is used for every HTTP request.
 * Magento\Framework\HTTP\Client\Curl is a shared singleton whose internal $_headers array is
 * overwritten with response headers after each post() call. Reusing the same instance for a
 * second request causes stale response headers (http_code, content-type, etc.) to be sent as
 * request headers, which makes the server return a non-JSON error response.
 * Using CurlFactory::create() per call mirrors SSO's curl_init() per call approach.
 */
class Curl
{
    private const TIMEOUT = 10;

    public function __construct(
        private readonly CurlFactory $curlFactory
    ) {}

    // ── Account management ────────────────────────────────────────────────────
    // All use DEFAULT credentials — customer not yet authenticated at this stage.

    /**
     * Register a new MiniOrange customer.
     * Mirrors SSO Curl::create_customer().
     * Endpoint: /moas/rest/customer/add
     */
    public function registerCustomer(
        string $email,
        string $phone     = '',
        string $password  = '',
        string $company   = '',
        string $firstName = '',
        string $lastName  = ''
    ): array {
        $payload = [
            'companyName'    => $company,
            'areaOfInterest' => Constants::AREA_OF_INTEREST,
            'firstname'      => $firstName,
            'lastname'       => $lastName,
            'email'          => $email,
            'phone'          => $phone,
            'password'       => $password,
        ];
        return $this->postWithAuth(Constants::REGISTER_URL, $payload);
    }

    /**
     * Authenticate with MiniOrange and retrieve the customer key + apiKey.
     * Mirrors SSO Curl::get_customer_key().
     * Endpoint: /moas/rest/customer/key
     * Returns ['status' => 'SUCCESS', 'id' => '...', 'apiKey' => '...'] on success.
     */
    public function loginCustomer(string $email, string $password): array
    {
        $payload = [
            'email'    => $email,
            'password' => $password,
        ];
        return $this->postWithAuth(Constants::LOGIN_URL, $payload);
    }

    /**
     * Check whether an email is already registered in miniOrange.
     * Mirrors SSO Curl::check_customer().
     * Endpoint: /moas/rest/customer/check-if-exists
     */
    public function checkCustomerExists(string $email): array
    {
        return $this->postWithAuth(Constants::CHECK_CUSTOMER_URL, ['email' => $email]);
    }

    /**
     * Send a password-reset link to the given email.
     * Mirrors SSO Curl::forgot_password().
     * Endpoint: /moas/rest/customer/password-reset
     */
    public function forgotPassword(string $email): array
    {
        return $this->postWithAuth(Constants::FORGOT_PASSWORD_URL, ['email' => $email]);
    }

    // ── License management ────────────────────────────────────────────────────
    // All use the CUSTOMER's own credentials (ckl + apiKey stored after login).
    // Mirrors SSO CommonUtility::ccl/vml/mius which retrieve CUSTOMER_KEY + API_KEY from DB.

    /**
     * Validate a license key against the MiniOrange API.
     * Mirrors SSO Curl::vml() — uses customer credentials for auth header.
     * Endpoint: /moas/api/backupcode/verify
     *
     * @param string $lk     License key to validate
     * @param string $ckl    Customer key (stored after login as 'id')
     * @param string $apiKey Customer's API key (stored after login as 'apiKey')
     * @param string $baseUrl Site base URL passed as additionalFields.field1
     */
    public function validateLicenseKey(string $lk, string $ckl, string $apiKey, string $baseUrl = ''): array
    {
        $payload = [
            'code'             => $lk,
            'customerKey'      => $ckl,
            'additionalFields' => ['field1' => $baseUrl]
        ];
        return $this->postWithCustomerAuth(Constants::LICENSE_VALIDATE_URL, $payload, $ckl, $apiKey);
    }

    /**
     * Mark a license code as inactive on the miniOrange server.
     * Called on account removal to free the license seat.
     * Mirrors SSO Curl::update_status() / CommonUtility::mius() — uses customer credentials.
     * Endpoint: /moas/api/backupcode/updatestatus
     */
    public function updateLicenseStatus(string $ckl, string $apiKey, string $code, string $baseUrl = ''): array
    {
        $payload = [
            'code'             => $code,
            'customerKey'      => $ckl,
            'additionalFields' => ['field1' => $baseUrl],
        ];
        return $this->postWithCustomerAuth(Constants::LICENSE_UPDATE_STATUS_URL, $payload, $ckl, $apiKey);
    }

    /**
     * Check the customer's license plan with miniOrange.
     * Mirrors SSO Curl::ccl() + CommonUtility::ccl() — uses customer credentials for auth header.
     * Endpoint: /moas/rest/customer/license
     *
     * @param string $ckl         Customer key (stored after login as 'id')
     * @param string $apiKey      Customer's API key (stored after login as 'apiKey')
     * @param string $applicationName Plan application name to check
     */
    public function checkCustomerLicense(string $ckl, string $apiKey, string $applicationName = ''): array
    {
        $payload = [
            'customerId'      => $ckl,
            'applicationName' => $applicationName ?: Constants::PLUGIN_ID,
        ];
        return $this->postWithCustomerAuth(Constants::CUSTOMER_LICENSE_URL, $payload, $ckl, $apiKey);
    }

    // ── Support ───────────────────────────────────────────────────────────────

    /**
     * Submit a Contact Us / support query to miniOrange.
     * Mirrors SSO Curl::submit_contact_us() — uses DEFAULT credentials for auth header.
     * Endpoint: /moas/rest/customer/contact-us
     */
    public function submitContactUs(string $email, string $phone, string $query): array
    {
        $payload = [
            'email'   => $email,
            'phone'   => $phone,
            'query'   => '[Magento 2.0 Personal Data Protection Plugin]: ' . $query,
            'ccEmail' => 'magentosupport@xecurify.com',
        ];
        return $this->postWithAuth(Constants::CONTACT_URL, $payload);
    }

    // ── Tracking ──────────────────────────────────────────────────────────────

    /**
     * Fire a tracking event to the plugin-portal.
     * Mirrors SSO Curl::submit_to_magento_team() (best-effort, never throws).
     */
    public function track(array $payload): void
    {
        try {
            $this->postRaw(Constants::PORTAL_URL, $payload);
        } catch (\Throwable $e) {
            // Best-effort: never throw from tracking
        }
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Build the miniOrange SHA-512 Authorization header array.
     * Mirrors SSO Curl::createAuthHeader().
     */
    private function createAuthHeader(string $customerKey, string $apiKey): array
    {
        $timestampMs = (string) round(microtime(true) * 1000);
        $authHash    = hash('sha512', $customerKey . $timestampMs . $apiKey);

        return [
            'Content-Type'  => 'application/json',
            'Customer-Key'  => $customerKey,
            'Timestamp'     => $timestampMs,
            'Authorization' => $authHash,
        ];
    }

    /**
     * POST with DEFAULT miniOrange credentials.
     * Used for account management endpoints (register, login, check, forgot-password)
     * where no customer session exists yet.
     */
    private function postWithAuth(string $url, array $data): array
    {
        return $this->doPost($url, $data, Constants::DEFAULT_CUSTOMER_KEY, Constants::DEFAULT_API_KEY);
    }

    /**
     * POST with the authenticated customer's own credentials.
     * Used for license management endpoints (ccl, vml, mius) — mirrors SSO pattern where
     * CommonUtility::ccl/vml/mius retrieve CUSTOMER_KEY + API_KEY from DB and pass them to Curl.
     */
    private function postWithCustomerAuth(string $url, array $data, string $customerKey, string $apiKey): array
    {
        return $this->doPost($url, $data, $customerKey, $apiKey);
    }

    /**
     * Shared HTTP POST implementation used by postWithAuth and postWithCustomerAuth.
     *
     * Creates a fresh Curl instance per call via CurlFactory — mirrors SSO's curl_init() per call.
     * This prevents stale response headers from a previous request from polluting the next request's
     * CURLOPT_HTTPHEADER (a side-effect of Magento's shared Curl singleton overwriting $_headers
     * with response headers after each makeRequest() call).
     */
    private function doPost(string $url, array $data, string $customerKey, string $apiKey): array
    {
        $headers = $this->createAuthHeader($customerKey, $apiKey);
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(self::TIMEOUT);
            foreach ($headers as $name => $value) {
                $curl->addHeader($name, $value);
            }
            $curl->post($url, json_encode($data) ?: '{}');
            $body    = $curl->getBody();
            $decoded = json_decode((string) $body, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // API returned non-array JSON (e.g. `true`) or plain text — use HTTP status as fallback
            return $curl->getStatus() >= 200 && $curl->getStatus() < 300
                ? ['status' => 'SUCCESS']
                : ['status' => 'error', 'message' => 'Invalid credentials'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Plain POST without auth headers (for plugin-portal tracking calls).
     * Also uses a fresh instance to stay consistent and avoid header pollution.
     */
    private function postRaw(string $url, array $data): array
    {
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(self::TIMEOUT);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->post($url, json_encode($data) ?: '{}');
            $body    = $curl->getBody();
            $decoded = json_decode((string) $body, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // API returned non-array JSON (e.g. `true`) or plain text — use HTTP status as fallback
            return $curl->getStatus() >= 200 && $curl->getStatus() < 300
                ? ['status' => 'SUCCESS']
                : ['status' => 'error', 'message' => 'Invalid credentials'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
