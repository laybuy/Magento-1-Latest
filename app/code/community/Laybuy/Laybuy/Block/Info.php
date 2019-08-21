<?php

/**
 * Class Laybuy_Laybuy_Block_Info
 */
class Laybuy_Laybuy_Block_Info extends Mage_Payment_Block_Info
{
    const KEY_REFERENCE_ORDER_ID = 'reference_order_id';
    const KEY_REFERENCE_TOKEN = 'reference_token';

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_customTemplate = 'laybuy/payment/info.phtml';

    /**
     * @var null|Laybuy_Laybuy_Model_Config
     */
    protected $_config;

    /**
     * Override parent method
     * Change template for current block
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate($this->_customTemplate);
    }

    /**
     * @return false|Laybuy_Laybuy_Model_Config|Mage_Core_Model_Abstract|null
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_config = Mage::getModel('laybuy/config');
        }

        return $this->_config;
    }

    /**
     * @return string
     */
    public function getLogoSrc()
    {
        return $this->getConfig()->getLogoSrc();
    }

    /**
     * @return array
     */
    public function getPreparedAdditionalInformation()
    {
        return array(
            'payment_logo' => $this->getLogoSrc(),
        );

    }

    /**
     * @param null $transport
     * @return null|Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);

        $additionalInfoHeadings = array(
            'laybuy_order_id'  => $this->__('Laybuy Order Id'),
            'result'           => $this->__('Laybuy Status'),
            'error'            => $this->__('Error Response'),
            'errordescription' => $this->__('Error Description'),
            'transactionid'    => $this->__('Transaction Id'),
        );

        $data = array();
        foreach ($additionalInfoHeadings as $key => $heading) {
            if ($infoData = $this->getInfo()->getAdditionalInformation($key)) {
                if ($key == 'error') {
                    $infoData = str_replace('Unexpected payment status: ', '', $infoData);
                }
                $data[$heading] = $infoData;
            }
        }

        if ($order = Mage::registry('current_order')) {
            $data['Laybuy Merchant Id'] = $this->getConfig()->getMerchantId($order->getStoreId());
        }

        $transport->setData(array_merge($data, $transport->getData()));
        $this->_paymentSpecificInformation = $transport->getData();
        return $transport;
    }
}
