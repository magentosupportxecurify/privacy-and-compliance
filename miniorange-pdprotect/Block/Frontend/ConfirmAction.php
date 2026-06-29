<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ConfirmAction extends Template
{
    private const VALID_ACTIONS = ['download', 'anonymize', 'delete', 'withdraw'];

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getAction(): string
    {
        $action = (string) $this->getRequest()->getParam('action', '');
        return in_array($action, self::VALID_ACTIONS, true) ? $action : '';
    }

    public function getPrivacyUrl(): string
    {
        return $this->getUrl('mopdp/customer/privacy');
    }

    public function getActionUrl(): string
    {
        return match ($this->getAction()) {
            'download' => $this->getUrl('mopdp/customer/downloaddata'),
            'anonymize' => $this->getUrl('mopdp/customer/anonymizedata'),
            'delete'   => $this->getUrl('mopdp/customer/requestdeletion'),
            'withdraw' => $this->getUrl('mopdp/customer/withdrawconsent'),
            default    => '',
        };
    }

    /**
     * GET endpoint that serves the file prepared by DownloadData (POST).
     * Only relevant when action === 'download'.
     */
    public function getDownloadServeUrl(): string
    {
        return $this->getUrl('mopdp/customer/downloadserve');
    }

    /**
     * @return array{title: string, description: string, note: string, button: string, icon: string}
     */
    public function getActionConfig(): array
    {
        return match ($this->getAction()) {
            'download' => [
                'title'       => __('Download My Data')->render(),
                'description' => __('Export a copy of all your personal information including account details, addresses, orders, and cart.')->render(),
                'note'        => __('Your data will be provided in a secure file for your records.')->render(),
                'button'      => __('Download My Data')->render(),
                'icon'        => 'download',
            ],
            'anonymize' => [
                'title'       => __('Anonymize My Data')->render(),
                'description' => __('Replace your personal details with anonymized values while keeping your order history intact. This action cannot be undone.')->render(),
                'note'        => __('Your identity will be anonymized but your past orders will be preserved.')->render(),
                'button'      => __('Anonymize My Data')->render(),
                'icon'        => 'anonymize',
            ],
            'delete' => [
                'title'       => __('Delete My Account')->render(),
                'description' => __('Submit a request to permanently delete your account and all associated personal data. An admin will review and process your request.')->render(),
                'note'        => __('This action is irreversible and all your data will be permanently removed.')->render(),
                'button'      => __('Request Account Deletion')->render(),
                'icon'        => 'delete',
            ],
            'withdraw' => [
                'title'       => __('Withdraw Consent')->render(),
                'description' => __('Revoke your previously given privacy consent. The popup will be shown again on your next visit.')->render(),
                'note'        => __('You can update your preferences anytime from your privacy settings.')->render(),
                'button'      => __('Withdraw Consent')->render(),
                'icon'        => 'withdraw',
            ],
            default => [],
        };
    }
}
