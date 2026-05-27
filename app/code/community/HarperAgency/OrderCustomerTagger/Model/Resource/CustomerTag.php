<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Resource_CustomerTag
 */
class HarperAgency_OrderCustomerTagger_Model_Resource_CustomerTag extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/customer_tag', 'id');
    }
}
