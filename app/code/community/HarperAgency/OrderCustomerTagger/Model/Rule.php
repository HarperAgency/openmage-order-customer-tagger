<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Rule
 */
class HarperAgency_OrderCustomerTagger_Model_Rule extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('harper_tagger/rule');
    }

    /**
     * Decode the conditions JSON column into an array.
     *
     * @return array
     */
    public function getConditionsArray()
    {
        $raw = $this->getData('conditions');
        if (empty($raw)) {
            return array();
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : array();
    }
}
