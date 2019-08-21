<?php

class Laybuy_Laybuy_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'laybuy/payment/form.phtml';

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
        $this->setMethodTitle('');
        $this->setMethodLabelAfterHtml($this->getMethodTitleImage());
        $this->setTemplate($this->_template);
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
    public function getMethodTitleImage()
    {
        return sprintf(
            '<img class="laybuy_payments-checkout-title" src="%s" alt="%s"/>',
            $this->getConfig()->getLogoSrc(),
            $this->getConfig()->getTitle()
        );
    }

    /**
     * @return bool
     */
    public function canShowRedirectMessage()
    {
        return true;
    }
}
