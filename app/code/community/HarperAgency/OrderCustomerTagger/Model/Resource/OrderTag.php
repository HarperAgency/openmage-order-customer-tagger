<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Resource_OrderTag
 */
class HarperAgency_OrderCustomerTagger_Model_Resource_OrderTag extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/order_tag', 'id');
    }
}
