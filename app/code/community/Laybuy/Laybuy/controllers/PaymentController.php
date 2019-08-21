<?php

/**
 * Class Laybuy_Laybuy_PaymentController
 */
class Laybuy_Laybuy_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var null|Laybuy_Laybuy_Model_Laybuy
     */
    protected $_laybuy;

    /**
     * @var null|Laybuy_Laybuy_Model_Config
     */
    protected $_config;

    /**
     * @var null|Laybuy_Laybuy_Model_Logger
     */
    protected $_logger;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = false;

    /**
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_laybuy = Mage::getModel('laybuy/laybuy');
        $this->_config = Mage::getModel('laybuy/config');
        $this->_logger = Mage::getModel('laybuy/logger');
        parent::_construct();
    }

    /**
     * Retrieve redirect URL
     */
    public function processAction()
    {
        if (! $this->getRequest()->isAjax()) {
            $this->_redirectToCart();
        }

        if (! $this->_validateFormKey()) {
            $this->getResponse()
                ->setHeader('Content-type', 'application/x-json')
                ->setBody(Mage::helper('core')->jsonEncode(array(
                    'success' => false,
                    'error' => true,
                    'error_messages' => 'Invalid form key.'
                )));
            return;
        }

        $this->_logger->debug(array(
            __METHOD__ => 'Start Gateway'
        ));

        $result = array(
            'success' => false,
            'error' => true,
            'error_messages' => 'Couldn\'t initialize LayBuy payment method.'
        );

        try {
            if (Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE === $this->_config->getPaymentAction()) {
                /** @var Mage_Customer_Model_Session $customerSession */
                $customerSession = Mage::getSingleton('customer/session');
                $quote = $this->_getQuote();
                $email = ! $customerSession->isLoggedIn() ? $quote->getBillingAddress()->getEmail() : null;
                $redirectUrl = $this->_laybuy->getLaybuyRedirectUrl($email);

                if ($redirectUrl) {
                    $result = array(
                        'success' => true,
                        'error' => false,
                        'redirectUrl' => $redirectUrl,
                    );
                } else {
                    $this->_logger->debug(array(
                        __METHOD__ => 'Gateway Failed'
                    ));
                }
            } else {
                $result = array(
                    'success' => true,
                    'error' => false,
                    'redirectUrl' => false,
                );
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
            $this->_logger->debug(array('Gateway Failed' => $e->getTraceAsString()));
        }

        $this->getResponse()
            ->setHeader('Content-type', 'application/x-json')
            ->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Action Response
     */
    public function responseAction()
    {
        $this->_logger->debug(__METHOD__);
        $paymentAction = $this->_config->getPaymentAction();

        try {
            switch ($paymentAction) {
                case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                    $this->proceedAuthorizeCapture();
                    break;
                case Mage_Payment_Model_Method_Abstract::ACTION_ORDER:
                    $this->proceedOrder();
                    break;
                default:
                    Mage::throwException(sprintf('The payment action \'%s\' isn\'t support.', $paymentAction));
            }
        } catch (Exception $e) {
            $this->_getCheckoutSession()->addError('LayBuy: There was an error confirming your order');
            $this->_getCheckoutSession()->addException($e, $e->getMessage());
            $this->_logger->debug($e->getMessage());
            $this->_logger->debug(array('Process Error' => $e->getTraceAsString()));
        }

        $this->_redirectToCart();
    }

    /**
     * @param null $redirectUrl
     * @return void
     */
    protected function _redirectToSuccessPage($redirectUrl = null)
    {
        $this->setFlag('', 'no-dispatch', true);

        if ($redirectUrl) {
            $this->getResponse()->setRedirect($redirectUrl);
        } else {
            $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        }

        $this->getResponse()->sendHeaders();
        exit();
    }

    /**
     * @param string $message
     * @param null $redirectUrl
     * @return void
     */
    protected function _redirectToFailurePage($message, $redirectUrl = null)
    {
        $this->_getCheckoutSession()->addError($message);
        $this->setFlag('', 'no-dispatch', true);
        if ($redirectUrl) {
            $this->getResponse()->setRedirect($redirectUrl);
        } else {
            $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
        }
        $this->getResponse()->sendHeaders();
        exit();
    }

    /**
     * @return void
     */
    protected function _redirectToCart()
    {
        $this->setFlag('', 'no-dispatch', true);
        $this->_redirect('checkout/cart', ['_secure' => true]);
        $this->getResponse()->sendHeaders();
        exit();
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->_getCheckoutSession()->addError($errorMessage);
        $this->_redirectToCart();
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    private function proceedAuthorizeCapture()
    {
        $this->_logger->debug(__METHOD__);
        $this->validateQuote();
        $token = $this->getToken();

        $responseStatus = $this->getStatus();
        $this->_logger->debug('Laybuy response status: '  . $responseStatus);
        switch ($responseStatus) {
            case Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS:
                $quote = $this->_getQuote();
                $quote->collectTotals();

                /* always after collectTotals */
                if (!$this->_laybuy->isValidQuoteSignatureForCheckout()) {
                    $this->_logger->debug('Shopping cart totals do not match');
                }

                $params = array(
                    Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token,
                );
                if ($this->_laybuy->isValidQuoteCurrency($quote)) {
                    $params[Laybuy_Laybuy_Model_Config::API_KEY_AMOUNT] = $quote->getGrandTotal();
                    $params[Laybuy_Laybuy_Model_Config::API_KEY_CURRENCY] = $quote->getQuoteCurrencyCode();
                }
                try {
                    $laybuyResult = $this->_laybuy->confirmOrder($params);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_logger->debug('Unable to confirm Laybuy order.  ' . $e->getMessage());
                    $this->_createPendingTransaction($quote, null, $token, $e->getMessage());
                    $this->_redirectToCartAndShowError(
                        'We are unable to process your order at this time, please contact us so we can complete this order.'
                    );
                    return;
                }

                $resultStatus = (string)$laybuyResult->result;
                $this->_logger->debug('Laybuy status ' . $resultStatus . ' for token ' . $token);
                if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === $resultStatus && $laybuyResult->orderId) {
                    $layBuyOrderId = $laybuyResult->orderId;
                    $this->_logger->debug('Payment confirmed for Laybuy order ' . $layBuyOrderId);
                    $this->_getCheckoutSession()
                        ->setLastQuoteId($quote->getId())
                        ->setLastSuccessQuoteId($quote->getId())
                        ->clearHelperData();

                    $order = $this->saveOrder($layBuyOrderId);
                    if ($order) {
                        $this->_getCheckoutSession()
                            ->setLastOrderId($order->getId())
                            ->setLastRealOrderId($order->getIncrementId());
                        $quote->setIsActive(false)->save();
                        $this->_laybuy->updatePayment($order, $token, array('laybuy_order_id' => $layBuyOrderId));
                        $orderPlaceRedirectUrl = $this->_getQuote()->getPayment()->getOrderPlaceRedirectUrl();
                        $this->_getCheckoutSession()->addSuccess('Order successfully created.');
                        $this->_logger->debug('Order ' . $order->getIncrementId() . ' successfully created for Laybuy order '
                            . $layBuyOrderId . '. Redirect to success page');
                        $this->_redirectToSuccessPage($orderPlaceRedirectUrl);
                    } else {
                        $this->_logger->debug('Order creation failed. Attempt to cancel Laybuy order ' . $layBuyOrderId);
                        try {
                            $this->_laybuy->laybuyCancel($token);
                            $this->_redirectToCartAndShowError(
                                sprintf('LayBuy order ' . $layBuyOrderId . ' was cancelled. Redirect to cart page.')
                            );
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $this->_logger->debug('Unable to cancel Laybuy order.  ' . $e->getMessage());
                            $this->_createPendingTransaction($quote, $layBuyOrderId, $token, $e->getMessage());
                            if ($e->getMessage() == 'The order has already been completed.') {
                                $this->_logger->debug('Redirect to success page for pending transaction.');
                                $this->_getCheckoutSession()->setLastOrderId($quote->getReservedOrderId());
                                $this->_redirectToSuccessPage();
                            } else {
                                $this->_logger->debug('Redirect to cart page. Show contact us message.');
                                $this->_redirectToCartAndShowError(
                                    'We are unable to process your order at this time, please contact us so we can complete this order.'
                                );
                            }
                        }
                    }
                    return;
                } else {
                    $message = 'Laybuy payment failed.';
                    $displayMessage = 'Laybuy: There has been an error processing your payment.';
                    if (Laybuy_Laybuy_Model_Config::LAYBUY_DECLINED === $resultStatus) {
                        $message = $laybuyResult->error . '. Error: ' . $laybuyResult->errorcode . ' - ' . $laybuyResult->errordescription;
                        $displayMessage = 'LayBuy: Payment has been declined.';
                    } elseif (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === $resultStatus || $laybuyResult->error) {
                        $message = 'Laybuy payment error: ' . $laybuyResult->error;
                    }
                    $this->_logger->debug($message);
                    try {
                        $this->_laybuy->laybuyCancel($token);
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->_logger->debug('Unable to cancel Laybuy order.  ' . $e->getMessage());
                    }
                    $this->_redirectToCartAndShowError($displayMessage);
                    return;
                }
                break;
            case Laybuy_Laybuy_Model_Config::LAYBUY_CANCELLED:
                $this->_logger->debug('Laybuy Cancelled. Redirect to cart page.');
                try {
                    $this->_laybuy->laybuyCancel($token);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_logger->debug('Unable to cancel Laybuy order.  ' . $e->getMessage());
                }
                $this->_redirectToCartAndShowError('LayBuy: Payment was cancelled.');
                return;
                break;
            default:
                $this->_logger->debug('Laybuy error. Status: ' . $this->getStatus());
                try {
                    $this->_laybuy->laybuyCancel($token);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_logger->debug('Unable to cancel Laybuy order.  ' . $e->getMessage());
                }
        }

        $this->_logger->debug('Laybuy payment failed. Redirect to cart page.');
        $this->_redirectToCartAndShowError('LayBuy: There was an error, payment failed.');
    }

    /**
     * @throws Mage_Core_Exception
     */
    private function proceedOrder()
    {
        $this->_logger->debug(__METHOD__);
        $token = $this->getToken();
        $order = $this->_getCheckoutSession()->getLastRealOrder();

        if (! $order || ! $order->getId()) {
            $this->_laybuy->laybuyCancel($token);
            Mage::throwException('Unable to get last real order ID.');
        }

        $responseStatus = $this->getStatus();
        $this->_logger->debug('Laybuy response status: '  . $responseStatus);
        switch ($responseStatus) {
            case Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS:
                $params = array(Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $token);
                try {
                    $laybuyResult = $this->_laybuy->confirmOrder($params);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_logger->debug('Unable to confirm Laybuy order.  ' . $e->getMessage());
                    $this->_redirectToCartAndShowError(
                        'We are unable to process your order at this time, please contact us so we can complete this order.'
                    );
                    return;
                }

                $paymentData = array();
                $resultStatus = (string)$laybuyResult->result;
                $this->_logger->debug('Laybuy status ' . $resultStatus . ' for Magento order ' . $order->getIncrementId());
                if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === $resultStatus && $laybuyResult->orderId) {
                    $layBuyOrderId = $laybuyResult->orderId;
                    $paymentData['laybuy_order_id'] = $layBuyOrderId;
                    $this->_logger->debug('Magento order ' . $order->getIncrementId()
                        . ' confirmed for Laybuy order ' . $layBuyOrderId);
                    if ($this->_laybuy->processLaybuySuccessPayment($order, $token, $layBuyOrderId)) {
                        $this->_logger->debug('Magento order '  . $order->getIncrementId() . ' updated. Redirect to success page');
                        $this->_getCheckoutSession()->addSuccess('Order successfully created.');
                        $this->_redirectToSuccessPage();
                    } else {
                        $this->_logger->debug('Update error for Magento order '. $order->getIncrementId()
                            . ' for Laybuy order ' . $layBuyOrderId);
                        $this->_redirectToCartAndShowError(
                            'We are unable to process your order at this time, please contact us so we can complete this order.'
                        );
                    }
                    return;
                } else {
                    $message = 'Laybuy payment failed';
                    $displayMessage = 'Laybuy: There has been an error processing your payment.';
                    if (Laybuy_Laybuy_Model_Config::LAYBUY_DECLINED === $resultStatus) {
                        $message = 'Laybuy payment declined: ' . $laybuyResult->errordescription;
                        $displayMessage = 'LayBuy: Payment has been declined.';
                    } elseif (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === $resultStatus) {
                        $message = 'Laybuy payment error: ' . $laybuyResult->error;
                    }
                    $this->_logger->debug($message);
                    try {
                        $result = (array)$laybuyResult;
                        $data = array_merge($paymentData, $result);
                        $this->_laybuy->updatePayment($order, $token, $data);
                        $this->_laybuy->cancelMagentoOrder($order, $token, $message);
                    } catch (Exception $e) {
                        $this->_logger->debug('Error cancelling Magento order ' . $order->getIncrementId());
                        Mage::logException($e);
                    }
                    $this->_logger->debug('Redirect to checkout failure page');
                    $this->_redirectToFailurePage($displayMessage);
                    return;
                }
                break;
            case Laybuy_Laybuy_Model_Config::LAYBUY_CANCELLED:
                $this->_logger->debug(array(
                    'Payment was cancelled' => $order->getId(),
                    'Order Increment ID' => $order->getIncrementId(),
                ));
                try {
                    $this->_logger->debug('Laybuy order cancelled. Cancel Magento order ' . $order->getIncrementId());
                    $this->_laybuy->cancelMagentoOrder($order, $token, 'LayBuy: Payment was cancelled.');
                } catch (Exception $e) {
                    $this->_logger->debug('Error cancelling Magento order ' . $order->getIncrementId());
                    Mage::logException($e);
                }
                $this->_redirectToCartAndShowError('LayBuy: Payment was cancelled.');
                return;
                break;
            default:
                try {
                    $this->_logger->debug('Laybuy error. Cancel Magento order ' . $order->getIncrementId());
                    $this->_laybuy->cancelMagentoOrder($order, $token, 'LayBuy: There was an error, payment failed.');
                } catch (Exception $e) {
                    $this->_logger->debug('Error cancelling Magento order ' . $order->getIncrementId());
                    Mage::logException($e);
                }
        }
        $this->_logger->debug('Redirect to cart page');
        $this->_redirectToCartAndShowError('LayBuy: There was an error, payment failed.');
        return;
    }


    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function validateQuote()
    {
        $quote = $this->_getQuote();

        if ($quote->getIsMultiShipping()) {
            Mage::throwException('Invalid checkout type.');
        }

        return $this;
    }

    /**
     * @param null $layBuyOrderId
     * @return bool|Mage_Sales_Model_Order
     */
    private function saveOrder($layBuyOrderId = null)
    {
        $quote = $this->_getQuote();

        /** @var Mage_Checkout_Model_Api_Resource_Customer $customerResource */
        $customerResource = Mage::getModel('checkout/api_resource_customer');
        $isNewCustomer = $customerResource->prepareCustomerForQuote($quote);

        /** @var $service Mage_Sales_Model_Service_Quote */
        $service = Mage::getModel('sales/service_quote', $quote);

        try {
            $service->submitAll();
            $order = $service->getOrder();
        } catch (Exception $e) {
            $order = false;
        }

        if ($order && $order->getId()) {
            $this->_getCheckoutSession()->setLastOrderId($order->getId());
            if ($isNewCustomer) {
                try {
                    $customerResource->involveNewCustomer($quote);
                    $customer = $quote->getCustomer();
                    Mage::getModel('customer/session')->loginById($customer->getId());
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                array('order' => $order, 'quote' => $quote));
            $this->sendNewOrderEmail($order);

            if ($layBuyOrderId) {
                $order->addStatusHistoryComment(sprintf(
                    'Placed by LayBuy. Reference order ID: %s, token: %s',
                    $layBuyOrderId,
                    $this->getToken()
                ));

                try {
                    $order->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            return $order;
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return $this
     */
    private function sendNewOrderEmail(Mage_Sales_Model_Order $order)
    {
        try {
            $order->queueNewOrderEmail();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Return checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    private function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }


    /**
     * @return string
     */
    private function getToken()
    {
        return strtoupper((string)$this->getRequest()->getParam(Laybuy_Laybuy_Model_Config::API_KEY_TOKEN));
    }

    /**
     * @return string
     */
    private function getStatus()
    {
        return strtoupper((string)$this->getRequest()->getParam(Laybuy_Laybuy_Model_Config::API_KEY_STATUS));
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param string $layBuyOrderId
     * @param string $token
     * @param string $error
     */
    protected function _createPendingTransaction($quote, $layBuyOrderId, $token, $error)
    {
        $data = array(
            'quote_id'           => $quote->getId(),
            'order_increment_id' => $quote->getReservedOrderId(),
            'laybuy_order_id'    => $layBuyOrderId,
            'laybuy_token'       => $token,
            'error_message'      => $error,
            'store_id'           => $quote->getStoreId()
        );
        try {
            $this->_logger->debug('Create pending transaction for order ' . $quote->getReservedOrderId() . ' for Laybuy order ' . $layBuyOrderId . '.');
            Mage::getModel('laybuy/transaction')->setData($data)
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger->debug('Unable to create Laybuy pending transaction: ' . $e->getMessage());
        }
    }

    /**
     * @param $quote
     * @return string
     */
    public function getCheckoutMethod($quote)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            return Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if (Mage::helper('checkout')->isAllowedGuestCheckout($this->_quote)) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }
}
