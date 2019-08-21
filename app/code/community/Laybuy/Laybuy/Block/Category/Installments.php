<?php
class Laybuy_Laybuy_Block_Category_Installments extends Mage_Core_Block_Template
{
    /**
     * Laybuy configs
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getInstallmentsConfig()
    {
        return array(
            'logo'               => $this->_getLogo(),
            'minOrderAmount'     => Mage::getModel('laybuy/config')->getMinOrderTotal(),
            'maxOrderAmount'     => Mage::getModel('laybuy/config')->getMaxOrderTotal(),
            'currencySymbol'     => Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(),
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getModel('laybuy/config')->isActive() && Mage::helper('laybuy')->displayOnCategoryPage();
    }

    /**
     * @return string
     */
    protected function _getLogo()
    {
        return (Mage::helper('laybuy')->showFullLogoInBreakdown()) ?
            Mage::getModel('laybuy/config')->getLogoSrc()
            : Mage::getModel('laybuy/config')->getMiniLogoSrc();
    }
}
