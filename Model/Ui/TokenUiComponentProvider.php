<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Psr\Log\LoggerInterface;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var LoggerInterface
     * @since 3.1.0
     */
    private $logger;

    /**
     * @var string
     * @since 3.1.0
     */
    private $wppUrl;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param ConfigProvider $configProvider
     * @param LoggerInterface $logger
     * @since 3.1.0 Added logger
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->componentFactory = $componentFactory;
        $this->logger = $logger;
        $this->wppUrl = $configProvider->getWppUrl(ConfigProvider::CREDITCARD_CODE);
    }

    /**
     * Get UI component for token
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        if (isset($jsonDetails['expirationDate'])) {
            $jsonDetails['expirationDate'] = $this->formatExpirationDate($jsonDetails);
        }
        $component = $this->componentFactory->create([
            'config' => [
                'code' => ConfigProvider::CREDITCARD_VAULT_CODE,
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                'wppUrl' => $this->wppUrl,
                'wpp_txtype' => \Wirecard\ElasticEngine\Controller\Frontend\Creditcard::FRONTEND_CODE_CREDITCARD,
            ],
            'name' => 'Wirecard_ElasticEngine/js/view/payment/method-renderer/vault'
        ]);

        return $component;
    }

    /**
     * @param $jsonDetails
     * @return string
     * @since 3.1.0
     */
    private function formatExpirationDate($jsonDetails)
    {
        try {
            $expirationDate = new \DateTime($jsonDetails['expirationDate']);
            return $expirationDate->format('m/Y');
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $this->logger->debug('Could not format expiration date to m/Y', compact('message'));
        }
        return $jsonDetails['expirationDate'];
    }
}
