<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Resource_Rule
 */
class HarperAgency_OrderCustomerTagger_Model_Resource_Rule extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/rule', 'id');
    }
}
