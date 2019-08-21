<?php

/**
 * Class Laybuy_Laybuy_Model_Source_Payment_Action
 */
class Laybuy_Laybuy_Model_Source_Payment_Action
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Laybuy_Laybuy_Helper_Data $helper */
        $helper = Mage::helper('laybuy');

        return array(
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
                'label' => $helper->__('Order'),
            ),
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                'label' => $helper->__('Authorize and Capture'),
            )
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        $optionArray = $this->toOptionArray();

        foreach ($optionArray as $value => $lable) {
            $result[$value] = $lable;
        }

        return $result;
    }
}
