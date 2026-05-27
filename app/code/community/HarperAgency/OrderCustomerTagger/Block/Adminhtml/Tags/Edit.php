<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Edit
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId    = 'id';
        $this->_blockGroup  = 'harper_tagger';
        $this->_controller  = 'adminhtml_tags';

        $helper = Mage::helper('harper_tagger');

        $this->_updateButton('save',   'label', $helper->__('Save Tag'));
        $this->_updateButton('delete', 'label', $helper->__('Delete Tag'));

        $this->_addButton('saveandcontinue', array(
            'label'   => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit() {
                editForm.submit(\$('edit_form').action + 'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        $tag = Mage::registry('harper_tagger_tag');
        if ($tag && $tag->getId()) {
            return Mage::helper('harper_tagger')->__(
                "Edit Tag '%s'",
                $this->htmlEscape($tag->getName())
            );
        }
        return Mage::helper('harper_tagger')->__('New Tag');
    }
}
