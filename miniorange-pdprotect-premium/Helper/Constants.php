<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Helper;

class Constants
{
    // Core config paths
    const INSTALLATION_DATE   = 'pdprotect/premium/installation_date';
    const OAUTH_LK            = 'pdprotect/premium/oauth_lk';
    const OAUTH_CKL           = 'pdprotect/premium/oauth_ckl';
    const OAUTH_API_KEY       = 'pdprotect/premium/oauth_api_key';   // customer's own API key — mirrors SSO API_KEY
    const LICENSE_PLAN        = 'pdprotect/premium/license_plan';
    const CUSTOMER_EMAIL      = 'pdprotect/premium/customer_email';

    // Trial config paths
    const TRIAL_START_DATE    = 'pdprotect/trial/start_date';
    const TRIAL_EXTENDED      = 'pdprotect/trial/extended';
    const TRIAL_SKIPPED       = 'pdprotect/trial/skipped';
    const TRIAL_DAYS          = 7;
    const TRIAL_EXTENSION_DAYS = 7;

    // License plan identifiers
    const PLAN_TRIAL          = 'trial';
    const PLAN_BASIC          = 'basic';
    const PLAN_PREMIUM        = 'premium';

    // Tracking config — mirrors TwoFA: TIMESTAMP / TRIAL_PLAN_CONSTANT / TRIAL_TIMESTAMP
    const TRACKING_TIMESTAMP           = 'pdprotect/tracking/timestamp';
    const TRACKING_TRIAL_PLAN_CONSTANT = 'pdprotect/tracking/trial_plan_constant';
    const TRACKING_TRIAL_EXPIRED_SENT   = 'pdprotect/tracking/trial_expired_sent';  // PDProtect-specific
    const TRACKING_TRIAL_EXTENDED_SENT  = 'pdprotect/tracking/trial_extended_sent'; // PDProtect-specific, fire once
    const TRACKING_PLAN_ACTIVATION_SENT = 'pdprotect/tracking/plan_activation_sent'; // guard: plan activation tracked once

    // Plugin-portal endpoint
    const PORTAL_URL          = 'https://magento.miniorange.com/plugin-portal/api/tracking';

    // MiniOrange API credentials (shared "default" app credentials — same as SSO module)
    const DEFAULT_CUSTOMER_KEY = '16555';
    const DEFAULT_API_KEY      = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';
    const DEFAULT_TOKEN_VALUE  = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';

    // Area of interest — sent in register payload (mirrors SSO AREA_OF_INTEREST)
    const AREA_OF_INTEREST          = 'Magento 2.0 Personal Data Protection';

    // MiniOrange account endpoints
    const MO_HOST                   = 'https://login.xecurify.com';
    const REGISTER_URL              = self::MO_HOST . '/moas/rest/customer/add';
    const LOGIN_URL                 = self::MO_HOST . '/moas/rest/customer/key';
    const CHECK_CUSTOMER_URL        = self::MO_HOST . '/moas/rest/customer/check-if-exists';
    const FORGOT_PASSWORD_URL       = self::MO_HOST . '/moas/rest/customer/password-reset';
    const LICENSE_VALIDATE_URL      = self::MO_HOST . '/moas/api/backupcode/verify';
    const LICENSE_UPDATE_STATUS_URL = self::MO_HOST . '/moas/api/backupcode/updatestatus';
    const CUSTOMER_LICENSE_URL      = self::MO_HOST . '/moas/rest/customer/license';
    const CONTACT_URL               = self::MO_HOST . '/moas/rest/customer/contact-us';

    // License sync / expiry — stored after successful vml() (mirrors SSO LICENSE_TIME_STAMP / LICENSE_EXPIRY_DATE)
    const LICENSE_SYNC_DATE         = 'pdprotect/premium/license_sync_date';
    const LICENSE_EXPIRY_DATE       = 'pdprotect/premium/license_expiry_date';

    // Module identifier
    const MODULE_NAME         = 'MiniOrange_PDProtectPremium';
    const PLUGIN_ID           = 'magento_adobe_commerce_sso_premium_plan';

    // PDProtect plan application names — used in checkCustomerLicense() loop (mirrors SSO $plans array)
    const PLAN_APP_NAMES = [
        'magento_adobe_commerce_sso_premium_plan',
    ];
}
