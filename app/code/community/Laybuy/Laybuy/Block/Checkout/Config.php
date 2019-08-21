<?php

/**
 * Class Laybuy_Laybuy_Block_Checkout_Config
 */
class Laybuy_Laybuy_Block_Checkout_Config extends Mage_Core_Block_Template
{
    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return Laybuy_Laybuy_Model_Config::CODE;
    }

    /**
     * @return string
     */
    public function getPaymentAction()
    {
        /** @var Laybuy_Laybuy_Model_Config $config */
        $config = Mage::getModel('laybuy/config');

        return $config->getPaymentAction();
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl(
            'laybuy/payment/process',
            array(
                'form_key' => Mage::getSingleton('core/session')->getFormKey()
            )
        );
    }
}
