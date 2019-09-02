<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Wirecard\ElasticEngine\Model\Adminhtml\Source\ChallengeIndicator;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AccountInfo;

/**
 * Class AccountInfoFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AccountInfoFactory
{
    protected $customerSession;

    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * @param ChallengeIndicator $challengeIndicator
     * @return AccountInfo
     */
    public function create($challengeIndicator)
    {
        $accountInfo = new AccountInfo();
        $accountInfo->setAuthMethod(AuthMethod::GUEST_CHECKOUT);

        if ($this->customerSession->isLoggedIn()) {
            $accountInfo->setAuthMethod(AuthMethod::USER_CHECKOUT);
            $this->setUserData();
        }
        $accountInfo->setChallengeInd($challengeIndicator);

        return $accountInfo;
    }

    private function setUserData() {
        // TODO Implement account info based on logged in user
        /** @var CustomerInterface $dataModel */
        $dataModel = $this->customerSession->getCustomerData();
        $created = $dataModel->getCreatedAt();
        $updated = $dataModel->getUpdatedAt();
        //customer login timestamp

    }
}
