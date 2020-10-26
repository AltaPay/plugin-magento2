<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Sales\Model\Order\Config;
use SDM\Altapay\Model\TokenFactory;
use Magento\Payment\Helper\Data;
use SDM\Altapay\Model\ConfigProvider;

class Success extends \Magento\Checkout\Block\Onepage\Success
{

    private $dataToken;
    /**
     * @var ConfigProvider
     */
    private $dataPayment;

    /**
     * Success constructor.
     *
     * @param Context        $context
     * @param Session        $checkoutSession
     * @param Config         $orderConfig
     * @param HttpContext    $httpContext
     * @param TokenFactory   $dataToken
     * @param array          $data
     * @param ConfigProvider $dataPayment
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        TokenFactory $dataToken,
        array $data = [],
        ConfigProvider $dataPayment
    ) {
        parent::__construct(
            $context,
            $checkoutSession,
            $orderConfig,
            $httpContext,
            $data
        );
        $this->dataPayment = $dataPayment;
        $this->dataToken   = $dataToken;
    }

    public function getTokenData()
    {
        $order      = $this->_checkoutSession->getLastRealOrder();
        $customerId = $order->getCustomerId();

        if (!empty($customerId)) {
            $payment       = $order->getPayment();
            $paymentMethod = $this->dataPayment->getActivePaymentMethod();
            $method        = $payment->getMethod();
            $ccToken       = $payment->getAdditionalInformation('cc_token');

            if (!empty($ccToken) && isset($paymentMethod[$method])
                && isset($paymentMethod[$method]['enabledsavetokens'])
                && $paymentMethod[$method]['enabledsavetokens'] == 1
            ) {
                $model      = $this->dataToken->create();
                $collection = $model->getCollection()
                                    ->addFieldToSelect(['id'])
                                    ->addFieldToFilter('customer_id', $customerId)
                                    ->addFieldToFilter('token', $ccToken)
                                    ->getFirstItem();
                if (!empty($collection->getData())) {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return $order;
    }
}
