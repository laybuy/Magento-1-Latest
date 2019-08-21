<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'laybuy/pending_transaction'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('laybuy/pending_transaction'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Quote Id')
    ->addColumn('order_increment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Order Increment Id')
    ->addColumn('laybuy_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Laybuy Order Id')
    ->addColumn('laybuy_token', Varien_Db_Ddl_Table::TYPE_TEXT, 64, array(
        'nullable'  => true,
    ), 'Laybuy Token')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
    ), 'Created At')
    ->addColumn('processed_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Processed At')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable'  => false,
    ), 'Status')
    ->addColumn('error_message', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => true,
    ), 'Error Message')
    /**
     * Add unique index for id
     */
    ->addIndex($installer->getIdxName('laybuy/pending_transaction', 'quote_id',
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        'quote_id', array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    )
    /**
     * Add index on external_id
     */
    ->addIndex($installer->getIdxName('laybuy/pending_transaction', 'order_increment_id',
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        'order_increment_id', array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    )
    ->addIndex($installer->getIdxName('laybuy/pending_transaction', 'laybuy_order_id',
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
        'laybuy_order_id', array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    )
    ->setComment('Laybuy Pending Transactions Table');

$installer->getConnection()->createTable($table);

$installer->endSetup();
