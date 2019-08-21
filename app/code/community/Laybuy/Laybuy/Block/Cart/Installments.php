<?php
class Laybuy_Laybuy_Block_Cart_Installments extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Quote|null
     */
    public function getQuote()
    {
        $session = Mage::getSingleton('checkout/session');
        return ($session) ? Mage::getSingleton('checkout/session')->getQuote() : null;
    }

    /**
     * @return string|null
     */
    public function getInstallmentAmount()
    {
        if ($this->_isEnabled()) {
            $quote = $this->getQuote();
            if ($quote
                && $quote->getGrandTotal()
                && $quote->getGrandTotal() >= Mage::getModel('laybuy/config')->getMinOrderTotal()
                && $quote->getGrandTotal() <= Mage::getModel('laybuy/config')->getMaxOrderTotal()
            ) {
                return Mage::helper('core')->currency($quote->getGrandTotal() / 6, true, false);
            }
        }
        return null;
    }

    /**
     * Laybuy configs
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getInstallmentsConfig()
    {
        return array(
            'logo'           => $this->_getLogo(),
            'amount'         => $this->getInstallmentAmount(),
            'currencySymbol' => Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(),
        );
    }

    /**
     * @return bool
     */
    protected function _isEnabled()
    {
        return Mage::getModel('laybuy/config')->isActive() && Mage::helper('laybuy')->displayOnCartPage();
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
