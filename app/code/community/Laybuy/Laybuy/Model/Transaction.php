<?php

class Laybuy_Laybuy_Model_Transaction extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('laybuy/transaction');
    }

    /**
     * Set transaction status to processed
     *
     * @return $this
     */
    public function updateToProcessing()
    {
        $this->setProcessedAt(Mage::getSingleton('core/date')->gmtDate())
            ->setStatus('processed')
            ->save();
        return $this;
    }

    /**
     * Set transaction status to cancelled

     * @param string $message
     * @return $this
     */
    public function cancel($message)
    {
        if ($message) {
            $this->setCancelMessage($message);
        }
        $this->setProcessedAt(Mage::getSingleton('core/date')->gmtDate())
            ->setStatus('cancelled')
            ->save();
        return $this;
    }
}
