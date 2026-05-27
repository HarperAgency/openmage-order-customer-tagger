<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Edit_Form
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $tag    = Mage::registry('harper_tagger_tag');
        $helper = Mage::helper('harper_tagger');

        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post',
        ));

        $form->setHtmlIdPrefix('tag_');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $helper->__('Tag Information'),
            'class'  => 'fieldset-wide',
        ));

        if ($tag && $tag->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $helper->__('Name'),
            'title'    => $helper->__('Tag Name'),
            'required' => true,
        ));

        $fieldset->addField('slug', 'text', array(
            'name'  => 'slug',
            'label' => $helper->__('Slug'),
            'title' => $helper->__('Slug (unique identifier, e.g. high-value)'),
            'note'  => $helper->__('Lowercase letters, numbers, and hyphens only.'),
            'required' => true,
        ));

        $fieldset->addField('color', 'text', array(
            'name'  => 'color',
            'label' => $helper->__('Color'),
            'title' => $helper->__('Hex color code, e.g. #e67e22'),
            'note'  => $helper->__('Enter a CSS hex color, e.g. #3498db'),
            'class' => 'validate-length maximum-length-7',
        ));

        $fieldset->addField('type', 'select', array(
            'name'   => 'type',
            'label'  => $helper->__('Type'),
            'title'  => $helper->__('Tag Type'),
            'values' => array(
                array('value' => 'order',    'label' => $helper->__('Order')),
                array('value' => 'customer', 'label' => $helper->__('Customer')),
            ),
        ));

        $fieldset->addField('image_url', 'text', array(
            'name'  => 'image_url',
            'label' => $helper->__('Image URL'),
            'title' => $helper->__('Optional image URL for this tag'),
        ));

        if ($tag) {
            $form->setValues($tag->getData());
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
