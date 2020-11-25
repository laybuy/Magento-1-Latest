<?php

/**
 * Class Laybuy_Laybuy_Block_Cms
 */
class Laybuy_Laybuy_Block_Cms extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Laybuy Information'));
        }
    }

    /**
     * Get Cms page iframe src based on merchant currency
     * @return string
     */
    public function getIframeSrc()
    {
        $currency = Mage::getModel('laybuy/config')->getMerchantCurrency();
        switch ($currency) {
            case 'AUD' :
                $code = 'au';
                break;
            case 'GBP' :
                $code = 'gb';
                break;
            case 'USD' :
                $code = 'us';
                break;
            default :
                $code = 'nz';
                break;
        }
        return 'https://integration-assets.laybuy.com/laybuy-cms-page/dist/index_' . $code . '.html';
    }
}
