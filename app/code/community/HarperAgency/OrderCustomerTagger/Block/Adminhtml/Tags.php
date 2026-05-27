<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags
 *
 * Container block for the Tags grid page.
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'harper_tagger';
        $this->_controller = 'adminhtml_tags';
        $this->_headerText = Mage::helper('harper_tagger')->__('Order & Customer Tags');

        parent::__construct();

        $this->_updateButton('add', 'label',
            Mage::helper('harper_tagger')->__('Add New Tag'));
    }
}
