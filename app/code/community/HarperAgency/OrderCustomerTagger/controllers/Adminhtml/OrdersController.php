<?php
/**
 * HarperAgency_OrderCustomerTagger_Adminhtml_OrdersController
 *
 * Handles mass actions initiated from the Sales → Orders grid.
 */
class HarperAgency_OrderCustomerTagger_Adminhtml_OrdersController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('sales/harper_tagger');
    }

    /**
     * Re-runs tagger rules on each selected order.
     * Called via the "Re-run Tagger Rules" mass action on the orders grid.
     */
    public function massRerunRulesAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids');
        if (!is_array($orderIds) || empty($orderIds)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Please select at least one order.'));
            $this->_redirect('adminhtml/sales_order/index');
            return;
        }

        /** @var HarperAgency_OrderCustomerTagger_Model_Observer $observer */
        $observer = Mage::getSingleton('harper_tagger/observer');

        $processed = 0;
        $errors    = 0;

        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load((int) $orderId);
            if (!$order->getId()) {
                continue;
            }
            try {
                $observer->processOrder($order);
                $processed++;
            } catch (Exception $e) {
                Mage::logException($e);
                $errors++;
            }
        }

        if ($processed > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Tagger rules re-run on %d order(s).',
                    $processed
                )
            );
        }
        if ($errors > 0) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__(
                    '%d order(s) could not be processed — check exception.log for details.',
                    $errors
                )
            );
        }

        $this->_redirect('adminhtml/sales_order/index');
    }
}
