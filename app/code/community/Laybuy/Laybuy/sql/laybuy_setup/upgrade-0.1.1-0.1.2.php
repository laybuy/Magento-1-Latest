<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('laybuy/pending_transaction'),
        'cancel_message',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => '255',
            'nullable'  => true,
            'comment' => 'Cancel Message'
        )
    );

$installer->getConnection()
    ->addColumn($installer->getTable('laybuy/pending_transaction'),
        'store_id',
        array(
            'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'nullable'  => true,
            'comment'   => 'Store Id'
        )
    );

$installer->endSetup();
