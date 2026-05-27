<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules
 *
 * Container block for the Rules grid page.
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'harper_tagger';
        $this->_controller = 'adminhtml_rules';
        $this->_headerText = Mage::helper('harper_tagger')->__('Order & Customer Rules');

        parent::__construct();

        $this->_updateButton('add', 'label',
            Mage::helper('harper_tagger')->__('Add New Rule'));
    }
}
