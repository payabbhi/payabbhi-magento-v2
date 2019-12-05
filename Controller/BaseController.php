<?php

namespace Payabbhi\Magento\Controller;

use Payabbhi\Client;
use Payabbhi\Magento\Model\Config;
use Magento\Framework\App\RequestInterface;

/**
 * Payabbhi Base Controller
 */
abstract class BaseController extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Payabbhi\Magento\Model\CheckoutFactory
     */
    protected $checkoutFactory;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote = false;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Payabbhi\Magento\Model\Checkout
     */
    protected $checkout;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Payabbhi\Magento\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payabbhi\Magento\Model\Config $config
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;

        $this->access_id = $this->config->getConfigData(Config::KEY_ACCESS_ID);
        $this->secret_key = $this->config->getConfigData(Config::KEY_SECRET_KEY);
        $this->version = $this->config->getConfigData(Config::VERSION);
        $this->name = $this->config->getConfigData(Config::NAME);

        $this->client = new Client($this->access_id, $this->secret_key);
        $this->client->setAppInfo($this->name, $this->version);
    }

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initCheckout()
    {
        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * Return checkout quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * @return \Payabbhi\Magento\Model\Checkout
     */
    protected function getCheckout()
    {
        if (!$this->checkout) {
            $this->checkout = $this->checkoutFactory->create(
                [
                    'params' => [
                        'quote' => $this->checkoutSession->getQuote(),
                    ],
                ]
            );
        }
        return $this->checkout;
    }
}
