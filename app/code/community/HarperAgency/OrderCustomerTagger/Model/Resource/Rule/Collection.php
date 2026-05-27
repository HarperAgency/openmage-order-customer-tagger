<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Resource_Rule_Collection
 */
class HarperAgency_OrderCustomerTagger_Model_Resource_Rule_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/rule');
    }
}
