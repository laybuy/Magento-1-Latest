<?php

/**
 * Class Logger
 */
class Laybuy_Laybuy_Model_Logger
{
    const LOG_FILENAME = 'laybuy_debug.log';

    /**
     * @var bool
     */
    private $canDebug;

    /**
     * @return bool
     */
    public function canDebug()
    {
        if (null === $this->canDebug) {
            /** @var Laybuy_Laybuy_Model_Config $config */
            $config = Mage::getModel('laybuy/config');

            $this->canDebug = $config->isDebugMode();
        }
        return $this->canDebug;
    }

    /**
     * @param $message
     * @param string $prefix
     * @return $this
     */
    public function debug($message, $prefix = '')
    {
        if($this->canDebug()) {
            if(! is_scalar($message)){
                $message = print_r($message,true);
            }

            Mage::log(sprintf('%s: %s', $prefix, $message), null, self::LOG_FILENAME);
        }
        return $this;
    }
}
