<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Resource_OrderTag_Collection
 */
class HarperAgency_OrderCustomerTagger_Model_Resource_OrderTag_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/order_tag');
    }
}
