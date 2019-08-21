<?php

/**
 * Class Laybuy_Laybuy_Helper_Data
 */
class Laybuy_Laybuy_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_SECTION_ID                    = 'laybuy';
    const XML_PATH_DISPLAY_ON_PRODUCT_PAGE     = 'payment/laybuy/display_product_page';
    const XML_PATH_PRODUCT_PRICE_BLOCK_CLASS  = 'payment/laybuy/product_price_block_class';
    const XML_PATH_DISPLAY_ON_CATEGORY_PAGE    = 'payment/laybuy/display_category_page';
    const XML_PATH_CATEGORY_PRICE_BLOCK_CLASS = 'payment/laybuy/category_price_block_class';
    const XML_PATH_DISPLAY_ON_CART_PAGE        = 'payment/laybuy/display_cart_page';
    const XML_PATH_SHOW_FULL_LOGO_IN_BREAKDOWN = 'payment/laybuy/display_full_logo';
    const XML_PATH_DISABLE_CMS_PAGE            = 'payment/laybuy/disable_cms_page';

    /**
     * @return bool
     */
    public function displayOnProductPage()
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_ON_PRODUCT_PAGE);
    }

    /**
     * @return string
     */
    public function getProductPriceClass()
    {
        $class = Mage::getStoreConfig(self::XML_PATH_PRODUCT_PRICE_BLOCK_CLASS);
        return (is_null($class) || $class == '') ? '.price-info .price-box' : $class;
    }

    /**
     * @return bool
     */
    public function showFullLogoInBreakdown()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHOW_FULL_LOGO_IN_BREAKDOWN);
    }

    /**
     * @return bool
     */
    public function displayOnCategoryPage()
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_ON_CATEGORY_PAGE);
    }

    /**
     * @return string
     */
    public function getCategoryPriceClass()
    {
        $class = Mage::getStoreConfig(self::XML_PATH_CATEGORY_PRICE_BLOCK_CLASS);
        return (is_null($class) || $class == '') ? '.product-info .price-box' : $class;
    }

    /**
     * @return bool
     */
    public function displayOnCartPage()
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_ON_CART_PAGE);
    }

    /**
     * @return bool
     */
    public function disableCmsPage()
    {
        return Mage::getStoreConfig(self::XML_PATH_DISABLE_CMS_PAGE);
    }

    /**
     * @param $imagePath
     * @return string
     */
    public function getMagentoAssetUrl($imagePath)
    {
        return 'https://integration-assets.laybuy.com/magento1_laybuy/' . $imagePath;
    }

    /**
     * @return string
     */
    public function getPopupIframeSrc()
    {
        return 'https://popup.laybuy.com/';
    }
}
