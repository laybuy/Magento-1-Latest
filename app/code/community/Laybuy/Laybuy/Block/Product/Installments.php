<?php
class Laybuy_Laybuy_Block_Product_Installments extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Catalog_Model_Product|mixed
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * @return string|null
     */
    public function getInstallmentAmount()
    {
        if ($this->_isEnabled()) {
            $product = $this->getProduct();
            if ($product
                && $product->getFinalPrice()
                && $product->getFinalPrice() >= Mage::getModel('laybuy/config')->getMinOrderTotal()
                && $product->getFinalPrice() <= Mage::getModel('laybuy/config')->getMaxOrderTotal()
            ) {
                return Mage::helper('core')->currency($product->getFinalPrice() / 6, true, false);
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
            'productId'          => $this->getProductId(),
            'logo'               => $this->_getLogo(),
            'amount'             => $this->getInstallmentAmount(),
            'currencySymbol'     => Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(),
            'priceBlockClass' => Mage::helper('laybuy')->getProductPriceClass(),
        );
    }

    /**
     * @return bool
     */
    protected function _isEnabled()
    {
        return Mage::getModel('laybuy/config')->isActive() && Mage::helper('laybuy')->displayOnProductPage();
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
