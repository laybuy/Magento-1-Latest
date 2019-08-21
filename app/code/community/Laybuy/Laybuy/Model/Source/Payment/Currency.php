<?php

class Laybuy_Laybuy_Model_Source_Payment_Currency {
    
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            //$this->_options = Mage::getModel('laybuy/laybuy')->getCurrencyList();
            $this->_options = Mage::getModel('laybuy/config')->getSupportedCurrencyCodes();
        }
        foreach ($this->_options as $option) {
            $options[$option] = $option;
        }
        array_unshift($options, array('value' => '', 'label' => '-- Please Select --'));
        return $options;
    }
}