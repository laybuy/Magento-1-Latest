<?php

class Laybuy_Laybuy_Model_Resource_Transaction_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('laybuy/transaction');
    }

    /**
     * @param string $id
     * @param string $value
     * @return array
     */
    public function toOptionArray($id = 'id', $value = 'quote_id')
    {
        return $this->_toOptionArray($id, $value);
    }

    /**
     * Filter by status and processed_at date
     * @return $this
     */
    public function addPendingFilter()
    {
        $this->addFilter('status', 'pending');
        $this->addFieldToFilter('processed_at', array('null' => true));
        return $this;
    }
}
