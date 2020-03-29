<?php

/**
 * Class Laybuy_Laybuy_Model_Laybuy_Api_Client_Rest
 */
class Laybuy_Laybuy_Model_Laybuy_Api_Client_Rest implements Laybuy_Laybuy_Model_Laybuy_Api_Client_Interface
{
    /**
     * @var null|string
     */
    private $endpoint;

    /**
     * @var null|string
     */
    private $merchantId;

    /**
     * @var null|string
     */
    private $merchantApiKey;

    /**
     * @var null|Zend_Rest_Client
     */
    private $zendRestClient;

    /**
     * @var null|Laybuy_Laybuy_Model_Logger
     */
    private $logger;

    /**
     * Laybuy_Laybuy_Model_Laybuy_Api_Client_Rest constructor.
     * @param int|null $storeId
     * @throws Zend_Http_Client_Exception
     * @throws Mage_Core_Exception
     */
    public function __construct($storeId = null)
    {
        $this->initializeLogger()
            ->initializeConfig($storeId)
            ->validateConfig()
            ->initializeZendRestClient();
    }

    /**
     * @return $this
     */
    private function initializeLogger()
    {
        $this->logger = Mage::getModel('laybuy/logger');

        return $this;
    }

    /**
     * @param int|null $storeId
     * @return $this
     */
    private function initializeConfig($storeId)
    {
        /** @var Laybuy_Laybuy_Model_Config $config */
        $config = Mage::getModel('laybuy/config');
        $this->endpoint = $config->isSandboxMode($storeId)
            ? $config->getApiEndpointSandbox($storeId)
            : $config->getApiEndpointLive($storeId);
        $this->merchantId = $config->getMerchantId($storeId);
        $this->merchantApiKey = $config->getMerchantApiKey($storeId);

        return $this;
    }

    /**
     * @return $this
     * @throws Zend_Http_Client_Exception
     */
    private function initializeZendRestClient()
    {
        $this->zendRestClient = new Zend_Rest_Client($this->endpoint);
        $this->zendRestClient->getHttpClient()
            ->setAuth($this->merchantId, $this->merchantApiKey, Zend_Http_Client::AUTH_BASIC);

        return $this;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function validateConfig()
    {
        if (! $this->isValidEndpoint()) {
            Mage::throwException('Invalid API  endpoint.');
        }

        if (! $this->isValidMerchantId()) {
            Mage::throwException('Invalid Merchant ID.');
        }

        if (! $this->isValidMerchantApiKey()) {
            Mage::throwException('Invalid Merchant API Key.');
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidEndpoint()
    {
        return ! empty($this->endpoint);
    }

    /**
     * @return bool
     */
    public function isValidMerchantId()
    {
        return ! empty($this->merchantId);
    }

    /**
     * @return bool
     */
    public function isValidMerchantApiKey()
    {
        return ! empty($this->merchantApiKey);
    }

    /**
     * @param $layBuyOrder
     * @return bool|string
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getRedirectUrl($layBuyOrder)
    {
        $result = $this->createOrder($layBuyOrder);
        $this->checkResponse($result);
        if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result
            && $result->paymentUrl
        ) {
            return $result->paymentUrl;
        }

        return false;
    }

    /**
     * @param $params
     * @return bool|string
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getLayBuyConfirmationOrderId($params)
    {
        $result = $this->confirmOrder($params);
        $this->checkResponse($result);
        if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result
            && $result->orderId
        ) {
            return $result->orderId;
        }
        return false;
    }

    /**
     * @param $reference
     * @return bool
     * @throws Zend_Http_Client_Exception
     */
    public function confirmMerchantOrder($reference)
    {
        $result = $this->confirmMerchant($reference);
        if (Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result
            && $result->merchantReference == $reference
        ) {
            return $result;
        }
        return false;
    }

    /**
     * @param $token
     * @return bool
     * @throws Zend_Http_Client_Exception
     * @throws Mage_Core_Exception
     */
    public function cancelLayBuyOrder($token)
    {
        $result = $this->cancelOrder($token);
        $this->checkResponse($result);
        return Laybuy_Laybuy_Model_Config::LAYBUY_SUCCESS === (string)$result->result;
     }

    /**
     * @param $token
     * @return bool|mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function cancelOrder($token)
    {
        $result = $this->_call(Laybuy_Laybuy_Model_Config::API_ORDER_CANCEL. '/' . $token);
        $this->checkResponse($result);
        return $result;
    }

    /**
     * @param $layBuyOrder
     * @return bool|mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createOrder($layBuyOrder)
    {
        $result = $this->_call(Laybuy_Laybuy_Model_Config::API_ORDER_CREATE, $layBuyOrder, Zend_Http_Client::POST);
        $this->checkResponse($result);
        return $result;
    }

    /**
     * @param array|string $params
     * @return bool|mixed
     * @throws Zend_Http_Client_Exception
     */
    public function confirmOrder($params)
    {
        if (is_string($params)) {
            $params = array(Laybuy_Laybuy_Model_Config::API_KEY_TOKEN => $params);
        }
        return $this->_call(Laybuy_Laybuy_Model_Config::API_ORDER_CONFIRM, $params, Zend_Http_Client::POST);
    }

    /**
     * @param $merchantReference
     * @return bool|mixed
     * @throws Zend_Http_Client_Exception
     */
    public function confirmMerchant($merchantReference)
    {
        $path = Laybuy_Laybuy_Model_Config::API_ORDER_MERCHANT . '/' . $merchantReference;
        return $this->_call($path, array(), Zend_Http_Client::GET);
    }

    /**
     * @param $params
     * @return bool|mixed
     * @throws Zend_Http_Client_Exception
     */
    public function refund($params)
    {
        $result = $this->_call(Laybuy_Laybuy_Model_Config::API_ORDER_REFUND, $params, Zend_Http_Client::POST);
        return $result;
    }

    /**
     * @return bool|mixed
     * @throws Zend_Http_Client_Exception
     */
    public function getCurrencies()
    {
        return $this->_call(Laybuy_Laybuy_Model_Config::API_OPTIONS_CURRENCIES);
    }

    /**
     * @param $path
     * @param $params
     * @param string $method
     * @return bool|mixed
     * @throws Zend_Http_Client_Exception
     */
    protected function _call($path, $params = array(), $method = Zend_Http_Client::GET)
    {
        if (! $this->zendRestClient) {
            return false;
        }

        if ($method == Zend_Http_Client::POST) {
            $response = $this->zendRestClient->restPost(
                $path,
                json_encode($params)
            );
        } else {
            $response = $this->zendRestClient->restGet(
                $path,
                $params
            );
        }
        $body = json_decode($response->getBody());
        $this->logger->debug(array($path => $body));
        return $body;
    }

    /**
     * @param $responseBody
     * @throws Mage_Core_Exception
     */
    private function checkResponse($responseBody)
    {
        if (! $responseBody || empty($responseBody->result)) {
            $this->logger->debug('LayBuy API Client: Something went wrong. Empty response.');
            Mage::throwException('Something went wrong. Empty response.');
        }

        if (Laybuy_Laybuy_Model_Config::LAYBUY_FAILURE === (string)$responseBody->result) {
            $message = ! empty($responseBody->error)
                ? $responseBody->error
                : 'Something went wrong. Failure response.';
            $this->logger->debug('LayBuy API Client: ' . $message);
            Mage::throwException($message);
        } elseif (Laybuy_Laybuy_Model_Config::LAYBUY_DECLINED === (string)$responseBody->result) {
            $message = 'LayBuy payment declined: ' . $responseBody->errordescription;
            $this->logger->debug($message);
            Mage::throwException($message);
        }
    }
}
