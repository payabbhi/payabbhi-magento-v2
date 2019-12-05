<?php

namespace Payabbhi\Magento\Controller\Payment;

use Payabbhi\Magento\Model\PaymentMethod;
use Magento\Framework\Controller\ResultFactory;

class Order extends \Payabbhi\Magento\Controller\BaseController
{
    protected $quote;

    protected $checkoutSession;

    protected $_currency = PaymentMethod::CURRENCY;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payabbhi\Model\CheckoutFactory $checkoutFactory
     * @param \Magento\Payabbhi\Model\Config\Payment $payabbhiConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payabbhi\Magento\Model\CheckoutFactory $checkoutFactory,
        \Payabbhi\Magento\Model\Config $config,
        \Magento\Catalog\Model\Session $catalogSession
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config
        );

        $this->checkoutFactory = $checkoutFactory;
        $this->catalogSession = $catalogSession;
    }

    public function execute()
    {
        $quote = $this->getQuote();
        $quote->reserveOrderId()->save(); // reserveOrderId is null initially

        $amount = (int) (round($quote->getBaseGrandTotal(), 2) * 100);
        $merchant_order_id = $quote->getReservedOrderId();

        $code = 400;

        $payabbhi_order_id = $this->catalogSession->getPayabbhiOrderID();

        try {
            if (($payabbhi_order_id === null) or
                              (($payabbhi_order_id and ($this->verify_order_amount($payabbhi_order_id)) === false))) {
                $payabbhi_order_id = $this->create_payabbhi_order();
            }

            $responseContent = [
                        'message'   => 'Unable to create order. Please contact support.',
                        'parameters' => []
                    ];

            if (!empty($payabbhi_order_id)) {
                $responseContent = [
                            'success'                           => true,
                            'payabbhi_order_id'                 => $payabbhi_order_id,
                            'merchant_order_id'                 => $merchant_order_id,
                            'amount'                            => $amount,
                            'quote_currency'                    => $quote->getQuoteCurrencyCode(),
                            'quote_amount'                      => round($quote->getGrandTotal(), 2)
                        ];

                $code = 200;
            }
        } catch (\Exception $e) {
            $responseContent = [
                    'message'   => $e->getMessage(),
                    'parameters' => []
                    ];
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);
        $response->setHttpResponseCode($code);

        return $response;
    }

    public function create_payabbhi_order()
    {
        $quote = $this->getQuote();

        $amount = (int) (round($quote->getBaseGrandTotal(), 2) * 100);

        $merchant_order_id = $quote->getReservedOrderId();

        $order = $this->client->order->create([
                      'amount' => $amount,
                      'merchant_order_id' => $merchant_order_id,
                      'currency' => $this->_currency,
                      'payment_auto_capture' => $this->config->getPaymentAutoCapture()
              ]);

        $this->catalogSession->setPayabbhiOrderID($order->id);

        return $order->id;
    }

    public function verify_order_amount($payabbhi_order_id)
    {
        $quote = $this->getQuote();

        $payabbhi_order = $this->client->order->retrieve($payabbhi_order_id);

        $payabbhi_order_args = array(
            'id'                  => $payabbhi_order->id,
            'amount'              => (int) (round($quote->getBaseGrandTotal(), 2) * 100),
            'currency'            => $this->_currency,
            'merchant_order_id'   => (string) $quote->getReservedOrderId(),
            );
        $orderKeys = array_keys($payabbhi_order_args);

        foreach ($orderKeys as $key) {
            if ($payabbhi_order_args[$key] !== $payabbhi_order[$key]) {
                return false;
            }
        }
        return true;
    }
}
