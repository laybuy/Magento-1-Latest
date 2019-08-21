<?php

class Laybuy_Laybuy_Model_Cron
{
    /**
     * @var
     */
    protected $log;

    /**
     * Process pending transactions
     */
    public function processPendingTransactions()
    {
        $transactions = Mage::getModel('laybuy/transaction')
            ->getCollection()
            ->addPendingFilter();
        if (count($transactions)) {
            $this->_getLog()->debug('Transactions to process: ' . count($transactions));
            foreach ($transactions as $transaction) {
                try {
                    Mage::getModel('laybuy/laybuy')->processPendingTransaction($transaction);
                    $this->_getLog()->debug('Processed pending transaction id: ' . $transaction->getId() . ' for Laybuy order ' . $transaction->getLaybuyOrderId());
                } catch (Exception $e) {
                    $this->_getLog()->debug('Transaction id ' . $transaction->getId() . ' process error: ' . $e->getMessage());
                }
            }
            $this->_getLog()->debug('Completed transaction processing.');
        }
    }

    /**
     * Process Laybuy payment review orders
     */
    public function processPendingOrders()
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $orders */
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->getSelect()
            ->joinLeft(
                array('payment' => 'sales_flat_order_payment'),
                'payment.parent_id = main_table.entity_id',
                array()
            )
            ->where('main_table.status = (?)', Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW)
            ->where('payment.method = ? ', Laybuy_Laybuy_Model_Config::CODE)
            ->where('main_table.updated_at + INTERVAL 15 MINUTE < ?', Mage::getSingleton('core/date')->gmtDate());
        if (count($orders)) {
            $this->_getLog()->debug('Pending Laybuy orders to process: ' . count($orders));
            foreach ($orders as $order) {
                try {
                    Mage::getModel('laybuy/laybuy')->processPendingOrder($order);
                } catch (Exception $e) {
                    $this->_getLog()->debug('Error updating pending Magento order ' . $order->getIncrementId() . ' - ' . $e->getMessage());
                }
            }
            $this->_getLog()->debug('Completed pending order processing.');
        }
    }

    /**
     * @return false|Laybuy_Laybuy_Model_Logger|Mage_Core_Model_Abstract
     */
    protected function _getLog()
    {
        if (is_null($this->log)) {
            $this->log = Mage::getModel('laybuy/logger');
        }
        return $this->log;
    }
}
