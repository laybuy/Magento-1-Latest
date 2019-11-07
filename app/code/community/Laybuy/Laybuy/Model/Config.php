<?php

/**
 * Class Laybuy_Laybuy_Model_Config
 */
class Laybuy_Laybuy_Model_Config
{
    /**
     * Payment unique code
     */
    const CODE = 'laybuy';

    /**
     * API settings
     */
    const API_ENDPOINT_LIVE = 'https://api.laybuy.com';
    const API_ENDPOINT_SANDBOX = 'https://sandbox-api.laybuy.com';
    const API_ORDER_CREATE = '/order/create';
    const API_ORDER_CONFIRM = '/order/confirm';
    const API_ORDER_CANCEL = '/order/cancel';
    const API_ORDER_REFUND = '/order/refund';
    const API_OPTIONS_CURRENCIES = '/options/currencies';

    const API_KEY_STATUS = 'status';
    const API_KEY_TOKEN = 'token';
    const API_KEY_AMOUNT = 'amount';
    const API_KEY_CURRENCY = 'currency';

    /**
     * Config key
     */
    const KEY_ACTIVE = 'active';
    const KEY_SANDBOX = 'sandbox';
    const KEY_DEBUG = 'debug';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_TRANSFER_LINE_ITEMS = 'transfer_line_items';
    const KEY_MERCHANT_ID = 'merchant_id';
    const KEY_MERCHANT_API_KEY = 'merchant_api_key';
    const KEY_MERCHANT_CURRENCY = 'merchant_currency';
    const KEY_TITLE = 'title';
    const KEY_MIN_ORDER_TOTAL = 'min_order_total';
    const KEY_MAX_ORDER_TOTAL = 'max_order_total';
    const KEY_QUOTE_SIGNATURE = 'laybuy_quote_signature';
    const KEY_ONESTEPCHECKOUT = 'is_onestepcheckout';

    /**
     * List of statuses for LayBuy API responses
     */
    const LAYBUY_SUCCESS = 'SUCCESS';
    const LAYBUY_DECLINED = 'DECLINED';
    const LAYBUY_FAILURE = 'ERROR';
    const LAYBUY_CANCELLED = 'CANCELLED';

    /**
     * List of supported currency codes
     */
    const SUPPORTED_CURRENCY_CODES = array('NZD', 'AUD', 'GBP');

    /**
     * @return string
     */
    public function getCode()
    {
        return self::CODE;
    }

    /**
     * @return string
     */
    public function getApiEndpointLive()
    {
        return self::API_ENDPOINT_LIVE;
    }

    /**
     * @return string
     */
    public function getApiEndpointSandbox()
    {
        return self::API_ENDPOINT_SANDBOX;
    }

    /**
     * @return array
     */
    public function getSupportedCurrencyCodes()
    {
        return self::SUPPORTED_CURRENCY_CODES;
    }

    /**
     * @param $code
     * @return bool
     */
    public function isSupportedCurrencyCode($code)
    {
        return in_array($code, $this->getSupportedCurrencyCodes());
    }

    /**
     * @param null $store
     * @return bool
     */
    public function getActive($store = null)
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isActive($store = null)
    {
        return (bool)$this->getActive($store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function getSandbox($store = null)
    {
        return (bool)$this->getValue(self::KEY_SANDBOX, $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isSandboxMode($store = null)
    {
        return (bool)$this->getSandbox($store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getDebug($store = null)
    {
        return $this->getValue(self::KEY_DEBUG, $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isDebugMode($store = null)
    {
        return (bool)$this->getDebug($store);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getPaymentAction($store = null)
    {
        return (string)$this->getValue(self::KEY_PAYMENT_ACTION, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getTransferLineItems($store = null)
    {
        return $this->getValue(self::KEY_TRANSFER_LINE_ITEMS, $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isTransferLineItems($store = null)
    {
        return (bool)$this->getTransferLineItems($store);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getMerchantId($store = null)
    {
        return (string)$this->getValue(self::KEY_MERCHANT_ID, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getMerchantApiKey($store = null)
    {
        return $this->decryptConfigValue((string)$this->getValue(self::KEY_MERCHANT_API_KEY, $store));
    }

    /**
     * @param null $store
     * @return string
     */
    public function getMerchantCurrency($store = null)
    {
        return $this->getValue(self::KEY_MERCHANT_CURRENCY, $store);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getTitle($store = null)
    {
        return (string)$this->getValue(self::KEY_TITLE, $store);
    }

    /**
     * @param null $store
     * @return float
     */
    public function getMinOrderTotal($store = null)
    {
        return (float)$this->getValue(self::KEY_MIN_ORDER_TOTAL, $store);
    }

    /**
     * @param null $store
     * @return float
     */
    public function getMaxOrderTotal($store = null)
    {
        return (float)$this->getValue(self::KEY_MAX_ORDER_TOTAL, $store);
    }

    /**
     * @param $value
     * @return mixed
     */
    private function decryptConfigValue($value)
    {
        return Mage::helper('core')->decrypt($value);
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getValue($key, $store = null)
    {
        $path = sprintf(
            'payment/%s/%s',
            Laybuy_Laybuy_Helper_Data::CONFIG_SECTION_ID,
            $key
        );

        return Mage::getStoreConfig($path, $store);
    }

    /**
     * @return string
     */
    public function getLogoSrc()
    {
        return Mage::helper('laybuy')->getMagentoAssetUrl('logo/full.svg');
    }

    /**
     * @return string
     */
    public function getMiniLogoSrc()
    {
        return Mage::helper('laybuy')->getMagentoAssetUrl('logo/small.svg');
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isOnestepCheckout($store = null)
    {
        return (bool)$this->getValue(self::KEY_ONESTEPCHECKOUT, $store);
    }
}
