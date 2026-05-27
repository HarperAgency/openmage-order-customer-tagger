<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_RulesCount
 *
 * Renders the number of rules associated with a tag in the admin grid.
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_RulesCount
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $tagId = (int) $row->getId();
        if (!$tagId) {
            return '0';
        }

        $collection = Mage::getModel('harper_tagger/rule')->getCollection();
        $collection->addFieldToFilter('tag_id', $tagId);
        return (string) $collection->getSize();
    }
}
