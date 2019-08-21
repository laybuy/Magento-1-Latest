<?php

class Laybuy_Laybuy_Model_Resource_Transaction
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('laybuy/pending_transaction', 'id');
    }

    /**
     * Set the created_at and status on initial save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract|void
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->isObjectNew()) {
            $object->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
            $object->setStatus('pending');
        }
        parent::_beforeSave($object);
    }
}
