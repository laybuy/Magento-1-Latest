<?php

/**
 * Class Laybuy_Laybuy_Model_Laybuy
 */
class Laybuy_Laybuy_Model_Laybuy extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = Laybuy_Laybuy_Model_Config::CODE;
    protected $_formBlockType = 'laybuy/form';
    protected $_infoBlockType = 'laybuy/info';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * @var null|Laybuy_Laybuy_Model_Laybuy_Api_Client_Interface
     */
    protected $_apiClient;

    /**
     * @var null|Laybuy_Laybuy_Model_Config
    */
    protected $_config;

    /**
     * @var null|Laybuy_Laybuy_Model_Logger
     */
    protected $_logger;


    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function order(Varien_Object $payment, $amount)
    {
        parent::order($payment, $amount);
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment->setIsTransactionPending(true);
        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $laybuyOrderId = $payment->getAdditionalInformation('laybuy_order_id');
        $incrementId = $payment->getOrder()->getIncrementId();

        if (!$laybuyOrderId) {
            $this->getLogger()->debug('Missing Laybuy order id for Magento order #' . $incrementId);
            Mage::throwException('Unable to process online refund due to invalid Laybuy order id.');
        }

        $params = array(
            'orderId'         => $laybuyOrderId,
            'amount'          => $amount,
        );
        $this->getLogger()->debug('Refund Laybuy order: ' . $laybuyOrderId . ' for Magento order #' . $incrementId);
        $result = $this->getApiClient()->refund($params);

        if (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === (string)$result->result) {
            $this->getLogger()->debug('LayBuy API Client error: ' . $result->error);
            $message = !empty($result->error) ? 'Laybuy API error: ' . $result->error : 'Laybuy API error';
            Mage::throwException($message);
        }

        if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result) {
            $message = 'Laybuy order ' . $laybuyOrderId . ' refunded.';
            $message .= !empty($result->refundId) ? ' Laybuy Refund Id: ' . $result->refundId : '';
            $this->getLogger()->debug($message);
            try {
                $payment->setAdditionalInformation('laybuy_refund_id', $result->refundId);
                $payment->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            Mage::throwException('Laybuy API error. Unable to process refund.');
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return $this|Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        $laybuyOrderId = $payment->getAdditionalInformation('laybuy_order_id');
        $refundId = $payment->getAdditionalInformation('laybuy_refund_id');
        $creditmemo->setTransactionId($laybuyOrderId . '_' . $refundId);
        return $this;
    }

    /**
     * @param int|null
     * @return Laybuy_Laybuy_Model_Laybuy_Api_Client_Interface
     * @throws Mage_Core_Exception
     */
    public function getApiClient($storeId = null)
    {
        if (null === $this->_apiClient) {
            $this->_apiClient = Mage::getModel('laybuy/laybuy_api_client_rest', $storeId);
        }

        if (! $this->_apiClient) {
            Mage::throwException('Invalid API client.');
        }

        return $this->_apiClient;
    }

    /**
     * @return null|Laybuy_Laybuy_Model_Config
     * @throws Mage_Core_Exception
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_config = Mage::getModel('laybuy/config');
        }

        if (! $this->_config) {
            Mage::throwException('Invalid Payment Config Provider.');
        }

        return $this->_config;
    }

    /**
     * @return null|Laybuy_Laybuy_Model_Logger
     */
    public function getLogger()
    {
        if (null === $this->_logger) {
            $this->_logger = Mage::getModel('laybuy/logger');
        }

        return $this->_logger;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getCheckoutRedirectUrl()
    {
        if (!$this->getConfig()->isOnestepCheckout()) {
            return null;
        }

        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        $quote = $this->getCheckout()->getQuote();
        $email = ! $customerSession->isLoggedIn() ? $quote->getBillingAddress()->getEmail() : null;
        return $this->getLaybuyRedirectUrl($email);
    }

    /**
     * @return bool|string
     * @throws Mage_Core_Exception
     */
    public function getOrderPlaceRedirectUrl()
    {
        if (Mage_Payment_Model_Method_Abstract::ACTION_ORDER === $this->getConfig()->getPaymentAction()) {
            /** @var Mage_Customer_Model_Session $customerSession */
            $customerSession = Mage::getSingleton('customer/session');
            $quote = $this->getCheckout()->getQuote();
            $email = ! $customerSession->isLoggedIn() ? $quote->getBillingAddress()->getEmail() : null;

            return $this->getLaybuyRedirectUrl($email);
        }

        return false;
    }

    /**
     * @param null|Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isActive($quote = null)
    {
        return (bool)(int)$this->getConfigData(Laybuy_Laybuy_Model_Config::KEY_ACTIVE, $quote ? $quote->getStoreId() : null);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return true;
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * @param null|Mage_Sales_Model_Quote $quote
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isAvailable($quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        if ($quote) {
            if (!$this->isActive($quote)
                || !$this->getConfig()
                || !$this->isValidQuoteBaseGrandTotal($quote)
                || !$this->isValidQuoteCurrency($quote)
            ) {
                return false;
            }

            try {
                $this->getApiClient($quote->getStoreId());
            } catch (Exception $e) {
                $this->getLogger()->debug('Laybuy API Client error: ' . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isValidQuoteCurrency(Mage_Sales_Model_Quote $quote)
    {
        return $this->getConfig()->getMerchantCurrency($quote->getStoreId()) == (string)$quote->getQuoteCurrencyCode();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     * @throws Mage_Core_Exception
     */
    private function isValidQuoteBaseGrandTotal(Mage_Sales_Model_Quote $quote)
    {
        $total = $quote->getGrandTotal();
        $minTotal = $this->getConfig()->getMinOrderTotal($quote->getStoreId());
        $maxTotal = $this->getConfig()->getMaxOrderTotal($quote->getStoreId());

        return (empty($minTotal) || $total >= $minTotal)
            && (empty($maxTotal) || $total <= $maxTotal);
    }

    /**
     * @param null|Mage_Sales_Model_Quote $quote
     * @throws Mage_Core_Exception
     */
    protected function validateQuote($quote)
    {
        if (! $quote
            || ! $quote->getItemsCount()
            || ! $this->isValidQuoteBaseGrandTotal($quote)
        ) {
            Mage::throwException('We can\'t initialize checkout.');
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function formatPrice($value)
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return stdClass
     * @throws Mage_Core_Exception
     */
    protected function createLaybuyOrder(Mage_Sales_Model_Quote $quote)
    {
        // Reserve order ID
        if ((self::ACTION_AUTHORIZE_CAPTURE === (string)$this->getConfigPaymentAction())
            || !$quote->getReservedOrderId()
        ) {
            $quote->reserveOrderId()->save();
        }

        // Create LayBuy order
        $layBuyOrder = new stdClass();
        $layBuyOrder->amount = $this->formatPrice($quote->getGrandTotal());
        if (!$this->isValidQuoteCurrency($quote)) {
            Mage::throwException('Laybuy merchant account does not accept this currency');
        }
        $layBuyOrder->currency = $quote->getQuoteCurrencyCode();

        // Tax is required for merchants who do not use the default tax rate and are from the United Kingdom
        if ($quote->getShippingAddress()->getTaxAmount() || $layBuyOrder->currency == 'GBP') {
            $layBuyOrder->tax = $quote->getShippingAddress()->getTaxAmount();
        }

        $layBuyOrder->returnUrl = $quote->getStore()->getUrl('laybuy/payment/response');
        $layBuyOrder->merchantReference = $quote->getReservedOrderId();

        // Set customer data for LayBuy order
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $layBuyOrder->customer = new stdClass();
        $layBuyOrder->customer->firstName = $billingAddress->getFirstname() ? $billingAddress->getFirstname() : $shippingAddress->getFirstname();
        $layBuyOrder->customer->lastName = $billingAddress->getLastname() ? $billingAddress->getLastname() : $shippingAddress->getLastname();
        $layBuyOrder->customer->email = $billingAddress->getEmail();

        $phone = (string)$billingAddress->getTelephone();

        if ('' === $phone || strlen(preg_replace('/[^0-9+]/i', '', $phone)) <= 6) {
            $phone = '00 000 000';
        }

        $layBuyOrder->customer->phone = $phone;
        $layBuyOrder->items = [];

        // Add items into LayBuy order
        if (! $this->getConfig()->isTransferLineItems()) {
            $layBuyOrder->items[0] = new stdClass();
            $layBuyOrder->items[0]->id = 1;
            $layBuyOrder->items[0]->description = $quote->getReservedOrderId();
            $layBuyOrder->items[0]->quantity = 1;
            $layBuyOrder->items[0]->price = $this->formatPrice($quote->getGrandTotal());
        } else {
            $layBuyOrder = $this->_getOrderItems($quote, $layBuyOrder);
        }

        $this->getLogger()->debug(
            array(
                __METHOD__ . ' CREATED ORDER' => $layBuyOrder,
            )
        );

        return $layBuyOrder;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param object $layBuyOrder
     * @return object
     */
    protected function _getOrderItems($quote, $layBuyOrder)
    {
        $i = 0;
        $totalPrice = 0;
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $price = ($item->getDiscountAmount()) ?
                $item->getPriceInclTax() - $item->getDiscountAmount() : $item->getPriceInclTax();
            if ($price == '0') {
                continue;
            }
            $layBuyOrder->items[$i]              = new stdClass();
            $layBuyOrder->items[$i]->id          = $item->getId();
            $layBuyOrder->items[$i]->description = $item->getName();
            $layBuyOrder->items[$i]->quantity    = $item->getQty();
            $layBuyOrder->items[$i]->price       = number_format($price, 2, '.', '');
            $totalPrice += $price * $item->getQty();
            $i++;
        }

        $shipping = $quote->getShippingAddress();
        $totalPrice += $shipping->getShippingInclTax();

        if (floatval($quote->getGrandTotal()) != floatval($totalPrice)) {
            $layBuyOrder->items[$i]              = new stdClass();
            $layBuyOrder->items[$i]->id          = 'DISCOUNT';
            $layBuyOrder->items[$i]->description = 'Discount';
            $layBuyOrder->items[$i]->quantity    = 1;
            $layBuyOrder->items[$i]->price       = number_format(floatval($quote->getGrandTotal()) - floatval($totalPrice), 2, '.', '');
            $i++;
        }

        if ($shipping->getShippingInclTax() > 0) {
            $layBuyOrder->items[$i]              = new stdClass();
            $layBuyOrder->items[$i]->id          = 'SHIPPING';
            $layBuyOrder->items[$i]->description = $shipping->getShippingDescription();
            $layBuyOrder->items[$i]->quantity    = 1;
            $layBuyOrder->items[$i]->price       = $shipping->getShippingInclTax();
        }
        return $layBuyOrder;
    }

    /**
     * Returns laybuy url
     *
     * @param bool|string $guestEmail
     * @return bool|string
     * @throws Mage_Core_Exception
     */
    public function getLaybuyRedirectUrl($guestEmail = null)
    {
        if (! $this->getApiClient()) {
            Mage::throwException('Invalid API client.');
        }

        $quote = $this->getCheckout()->getQuote();
        $this->validateQuote($quote);

        if (self::ACTION_AUTHORIZE_CAPTURE === (string)$this->getConfigPaymentAction()) {
            $payment = $quote->getPayment();
            $payment->setMethod(Laybuy_Laybuy_Model_Config::CODE);
        }

        $laybuyOrder = $this->createLaybuyOrder($quote);
        $result = $this->getApiClient()->createOrder($laybuyOrder);
        if ($result) {
            if ($result->token) {
                $payment = $quote->getPayment();
                $payment->setAdditionalInformation('token', $result->token);
            }
            $this->addQuoteSignatureForCheckout();
            if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result
                && $result->paymentUrl
            ) {
                return $result->paymentUrl;
            }
        }

        return false;
    }

    /**
     * Confirms laybuy order
     *
     * @param $params
     * @return bool|string
     * @throws Mage_Core_Exception
     */
    public function laybuyConfirm($params)
    {
        $laybuyOrderId = $this->getApiClient()->getLayBuyConfirmationOrderId($params);
        $this->getLogger()->debug(array(
                __METHOD__ . 'LAYBUY ORDER:' => $laybuyOrderId,
                $params
            )
        );
        return $laybuyOrderId;
    }

    /**
     * @param $params
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function confirmOrder($params)
    {
        return $this->getApiClient()->confirmOrder($params);
    }

    /**
     * @param $token
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function laybuyCancel($token)
    {
        $laybuyCancelResult = $this->getApiClient()->cancelLayBuyOrder($token);
        $this->getLogger()->debug(array(__METHOD__ . ' LAYBUY CANCEL STATUS:' => $laybuyCancelResult,
            Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token));
        return $laybuyCancelResult;
    }

    /**
     * Cancels magento order if laybuy didnt confirm order
     *
     * @param Mage_Sales_Model_Order $order
     * @param bool $token
     * @param bool $comment
     * @throws Exception
     */
    public function cancelMagentoOrder(Mage_Sales_Model_Order $order, $token = false, $comment = false)
    {
        if (! $order->isCanceled()
            && Mage_Sales_Model_Order::STATE_COMPLETE !== $order->getState()
            && Mage_Sales_Model_Order::STATE_CLOSED !== $order->getState()
        ) {
            $this->getLogger()->debug('Cancel Magento order ' . $order->getIncrementId());
            $this->beforeUpdateOrder($order);
            if ($comment) {
                $order->addStatusHistoryComment($comment);
            }
            $order->cancel();
            $order->save();
            if ($token) {
                $this->laybuyCancel($token);
            }
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $token
     * @param $laybuyOrderId
     * @return bool
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    public function processLaybuySuccessPayment(Mage_Sales_Model_Order $order, $token, $laybuyOrderId)
    {
        $this->beforeUpdateOrder($order);

        if ($order->canInvoice() && $this->shouldBeInvoiced($order)) {
            $data = array('laybuy_order_id' => $laybuyOrderId);
            $this->updatePayment($order, $token, $data);
            $this->createInvoiceAndUpdateOrder($order, $laybuyOrderId);

            $this->getLogger()->debug(array(
                __METHOD__ => 'Payment processed successfully',
                Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token,
                'Laybuy Order Id' => $laybuyOrderId
            ));

            return true;
        }

        $this->getLogger()->debug(array(
            __METHOD__ => 'Payment processed with failure',
            Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token,
            'Laybuy Order Id' => $laybuyOrderId,
            'Order Can Invoice' => $order->canInvoice(),
            'Order Should be Invoiced' => $this->shouldBeInvoiced($order)
        ));

        return false;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @param $orderId
     * @param $laybuyOrderId
     * @throws Exception
     */
    public function createInvoiceAndUpdateOrder(Mage_Sales_Model_Order $order, $laybuyOrderId)
    {
        // Update Order
        $orderStatus = $order->getConfig()->getStateDefaultStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)
            ->setStatus($orderStatus);
        $order->addStatusHistoryComment('Payment approved by Laybuy, Laybuy Order ID: ' . $laybuyOrderId);

        // Create Invoice
        $invoice = $order->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();

        /** @var Mage_Core_Model_Resource_Transaction $dbTransaction */
        $dbTransaction = Mage::getModel('core/resource_transaction');
        $dbTransaction->addObject($invoice)->addObject($invoice->getOrder())->save();

        $this->sendOrderEmail($order);

        $this->getLogger()->debug(
            array(
                __METHOD__ => 'Invoice created successfully',
                'Order Id' => $order->getId(),
                'Laybuy Order Id' => $laybuyOrderId,
            )
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function sendOrderEmail(Mage_Sales_Model_Order $order)
    {
        $orderIncrementId = '';
        try {
            $orderIncrementId = $order->getIncrementId();
            $order->sendNewOrderEmail();
        } catch (Exception $e) {
            $this->getLogger()->debug(
                array(
                    __METHOD__ => 'Can not send new order email.',
                    'Order Increment Id' => $orderIncrementId,
                )
            );
        }

    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return $this
     */
    protected function beforeUpdateOrder(Mage_Sales_Model_Order $order)
    {
        if ($order->isPaymentReview()) {
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
                ->setStatus('pending_payment');
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    protected function shouldBeInvoiced(Mage_Sales_Model_Order $order)
    {
        if ($order->hasInvoices()) {
            return false;
        }

        if (Laybuy_Laybuy_Model_Config::CODE !== $order->getPayment()->getMethod()) {
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $token
     * @param array $data
     */
    public function updatePayment(Mage_Sales_Model_Order $order, $token, $data)
    {
        try {
            $payment = $order->getPayment();
            if (isset($data['laybuy_order_id'])) {
                $payment->setTransactionId($data['laybuy_order_id'] . '_' . $token);
            }
            foreach ($data as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
            $payment->setAdditionalInformation('token', $token);
            $payment->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @return string
     */
    public function getPreparedQuoteSignature()
    {
        $quote =  $this->getCheckout()->getQuote();

        $keyInfo = array(
            $quote->getId(),
            round($quote->getGrandTotal()),
        );

        return hash('sha256', implode('-', $keyInfo));
    }

    /**
     * @return void
     */
    public function addQuoteSignatureForCheckout()
    {
        $this->getCheckout()->setData(
            Laybuy_Laybuy_Model_Config::KEY_QUOTE_SIGNATURE,
            $this->getPreparedQuoteSignature()
        );
    }

    /**
     * @return bool
     */
    public function isValidQuoteSignatureForCheckout()
    {
        $currentQuoteSignature = $this->getCheckout()->getData(Laybuy_Laybuy_Model_Config::KEY_QUOTE_SIGNATURE);

        return ! empty($currentQuoteSignature)
            && ($currentQuoteSignature === $this->getPreparedQuoteSignature());
    }

    /**
     * Get Laybuy available currencies list - not specific to merchant account
     * @return array
     */
    public function getCurrencyList()
    {
        $currencies = [];
        try {
            $result = $this->getApiClient()->getCurrencies();
            if ($result) {
                if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result
                    && $result->currencies
                ) {
                    foreach ($result->currencies as $currency){
                        $currencies[strtoupper($currency)] = strtoupper($currency);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $currencies;
    }

    /**
     * @param Laybuy_Laybuy_Model_Transaction $transaction
     * @return $this
     */
    public function processPendingTransaction($transaction)
    {
        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $info = $appEmulation->startEnvironmentEmulation($transaction->getStoreId());

        $token = $transaction->getLaybuyToken();
        $layBuyOrderId = $transaction->getLaybuyOrderId();
        if (!$layBuyOrderId) {
            $params = array(Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token);
            try {
                $laybuyResult = $this->confirmOrder($params);
            } catch (Exception $e) {
                $this->getLogger()->debug('Unable to confirm Laybuy order.  ' . $e->getMessage());
                $appEmulation->stopEnvironmentEmulation($info);
                return $this;
            }
            $resultStatus = (string)$laybuyResult->result;
            $this->getLogger()->debug('Laybuy status ' . $resultStatus);
            if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === $resultStatus && $laybuyResult->orderId) {
                $layBuyOrderId = $laybuyResult->orderId;
                $transaction->setData('laybuy_order_id', $layBuyOrderId)
                    ->save();
                $this->getLogger()->debug('Update pending transaction ' . $transaction->getId()
                    . ' with Laybuy order number ' . $layBuyOrderId);
                $appEmulation->stopEnvironmentEmulation($info);
                return $this;
            } else {
                $message = 'Laybuy payment failed.';
                if (Laybuy_Laybuy_Model_Config::LAYBUY_DECLINED === $resultStatus) {
                    $message = 'Laybuy payment declined: ' . $laybuyResult->errordescription;
                } elseif (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === $resultStatus) {
                    $message = 'Laybuy payment error: ' . $laybuyResult->error;
                }
                $this->getLogger()->debug('Cancel pending transaction. ' . $message);
                $transaction->cancel($message);
                $appEmulation->stopEnvironmentEmulation($info);
                return $this;
            }
        }

        $laybuy = Mage::getModel('laybuy/laybuy');
        $this->getLogger()->debug('Process pending transaction for laybuy order.  ' . $transaction->getLaybuyOrderId() . ' for order # ' . $transaction->getOrderIncrementId() . ' for quote id ' . $transaction->getQuoteId());

        if (!$transaction->getQuoteId() || !$transaction->getStoreId()) {
            $message = 'Invalid transaction data';
            $this->getLogger()->debug($message);
            $transaction->cancel($message);
            return $this;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->load($transaction->getQuoteId());
        if (!$quote->getId()) {
            $message = 'Quote does not exist for id ' . $transaction->getQuoteId();
            $this->getLogger()->debug($message);
            $transaction->cancel($message);
            $appEmulation->stopEnvironmentEmulation($info);
            return $this;
        }

        if (!$quote->getIsActive()) {
            $message = 'Quote is not active';
            $this->getLogger()->debug($message);
            $transaction->cancel($message);
            $appEmulation->stopEnvironmentEmulation($info);
            return $this;
        }

        try {
            $quote->collectTotals();
            /** @var $service Mage_Sales_Model_Service_Quote */
            $service = Mage::getModel('sales/service_quote', $quote);
            try {
                $service->submitAll();
                $order = $service->getOrder();
            } catch (Exception $e) {
                $order = false;
                $this->getLogger()->debug('Error creating order: ' . $e->getMessage());
            }

            if ($order && $order->getId()) {
                try {
                    $customerResource = Mage::getModel('checkout/api_resource_customer');
                    $customerResource->prepareCustomerForQuote($quote);
                    $order->addStatusHistoryComment(sprintf(
                        'Placed by LayBuy. Reference order ID: %s, token: %s',
                        $layBuyOrderId,
                        $token
                    ));
                    Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                        array('order' => $order, 'quote' => $quote));
                    $order->save();
                    try {
                        $order->queueNewOrderEmail();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                    $quote->setIsActive(false)->save();
                    $laybuy->updatePayment($order, $token, $layBuyOrderId);
                    $this->getLogger()->debug('Order ' . $order->getIncrementId() . ' successfully created from pending transaction for Laybuy order ' . $layBuyOrderId . '.');
                    $transaction->updateToProcessing();
                    $appEmulation->stopEnvironmentEmulation($info);
                    return $this;
                } catch (Exception $e) {
                    $message = 'Error processing order: ' . $e->getMessage();
                }
            } else {
                $message = 'Order failed to create from pending transaction for Laybuy order ' . $layBuyOrderId;
            }
        } catch (Exception $e) {
            $message = 'Error creating order from pending transaction for Laybuy order ' . $e->getMessage();
        }

        $this->getLogger()->debug($message);
        $transaction->cancel($message);
        $appEmulation->stopEnvironmentEmulation($info);
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function processPendingOrder($order)
    {
        if (!$order->getId() && !$order->getQuoteId()) {
            return;
        }

        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $info = $appEmulation->startEnvironmentEmulation($order->getStoreId());

        $token = null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote && $quote->getId()) {
            $token = $quote->getPayment()->getAdditionalInformation('token');
            if ($token) {
                $params = array(Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token);
                try {
                    $laybuyResult = $this->confirmOrder($params);
                } catch (Exception $e) {
                    $this->getLogger()->debug('Unable to confirm Laybuy order.  ' . $e->getMessage());
                    $appEmulation->stopEnvironmentEmulation($info);
                    return;
                }

                $resultStatus = (string)$laybuyResult->result;
                $this->getLogger()->debug('Laybuy status ' . $resultStatus . ' for pending Magento order ' . $order->getIncrementId());
                if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === $resultStatus && $laybuyResult->orderId) {
                    $layBuyOrderId = $laybuyResult->orderId;
                    $this->getLogger()->debug('Payment confirmed for Laybuy order ' . $layBuyOrderId . ' for Magento order ' . $order->getIncrementId());
                    try {
                        if ($this->processLaybuySuccessPayment($order, $token, $layBuyOrderId)) {
                            $this->getLogger()->debug('Magento pending order '  . $order->getIncrementId() . ' status updated.');
                        } else {
                            $this->getLogger()->debug('Unable to update pending Magento order '. $order->getIncrementId()
                                . ' for Laybuy order ' . $layBuyOrderId);
                        }
                    } catch (Exception $e) {
                        $this->getLogger()->debug('Error updating Magento order ' . $order->getIncrementId() . ': ' . $e->getMessage());
                    }
                    $appEmulation->stopEnvironmentEmulation($info);
                    return;
                }
                $message = 'Laybuy payment failed';
                if (Laybuy_Laybuy_Model_Config::LAYBUY_DECLINED === $resultStatus) {
                    $message = 'Laybuy payment declined: ' . $laybuyResult->errordescription;
                } elseif (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === $resultStatus) {
                    if (strpos($laybuyResult->error, 'ACTIVE') !== false) {
                        $this->getLogger()->debug('Laybuy user session still active, do not cancel.');
                        $appEmulation->stopEnvironmentEmulation($info);
                        return;
                    }
                    $message = 'Laybuy payment error: ' . $laybuyResult->error;
                }
                $result = (array)$laybuyResult;
                $this->updatePayment($order, $token, $result);
            } else {
                $message = 'Unable to confirm Laybuy order: missing token';
            }
        } else {
            $message = 'Unable to confirm Laybuy order: quote no longer available';
        }

        $this->getLogger()->debug($message);
        try {
            $this->cancelMagentoOrder($order, $token, $message);
        } catch (Exception $e) {
            $this->getLogger()->debug('Error cancelling Magento order ' . $order->getIncrementId() . ': ' . $e->getMessage());
            Mage::logException($e);
        }
        $appEmulation->stopEnvironmentEmulation($info);
        return;
    }
}
