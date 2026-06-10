<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Consent;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly Json $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $data = $this->serializer->unserialize((string) $this->request->getContent());
            $prefs = is_array($data) && isset($data['preferences']) && is_array($data['preferences'])
                ? $data['preferences']
                : [];

            $allowed = ['necessary', 'analytics', 'functional', 'marketing'];
            $clean = array_fill_keys($allowed, false);
            $clean['necessary'] = true;

            foreach ($allowed as $cat) {
                if (isset($prefs[$cat])) {
                    $clean[$cat] = (bool) $prefs[$cat];
                }
            }

            $result->setData(['success' => true, 'preferences' => $clean]);
        } catch (\Throwable $e) {
            $this->logger->warning('MO Cookie consent save failed: ' . $e->getMessage());
            $result->setData(['success' => false]);
        }

        return $result;
    }
}
