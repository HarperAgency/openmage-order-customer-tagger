<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Grid
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Tags_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('harperTaggerTagsGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('harper_tagger/tag')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('harper_tagger');

        $this->addColumn('id', array(
            'header' => $helper->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'id',
        ));

        $this->addColumn('name', array(
            'header'   => $helper->__('Name'),
            'align'    => 'left',
            'index'    => 'name',
            'renderer' => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_Badge',
        ));

        $this->addColumn('type', array(
            'header' => $helper->__('Type'),
            'align'  => 'left',
            'width'  => '90px',
            'index'  => 'type',
            'type'   => 'options',
            'options' => array(
                'order'    => $helper->__('Order'),
                'customer' => $helper->__('Customer'),
            ),
        ));

        $this->addColumn('color', array(
            'header' => $helper->__('Color'),
            'align'  => 'left',
            'width'  => '80px',
            'index'  => 'color',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('rules_count', array(
            'header'   => $helper->__('Rules'),
            'align'    => 'center',
            'width'    => '60px',
            'index'    => 'id',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_RulesCount',
        ));

        $this->addColumn('action', array(
            'header'    => $helper->__('Action'),
            'width'     => '120',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption' => $helper->__('Edit'),
                    'url'     => array('base' => '*/*/edit'),
                    'field'   => 'id',
                ),
                array(
                    'caption' => $helper->__('Delete'),
                    'url'     => array('base' => '*/*/delete'),
                    'field'   => 'id',
                    'confirm' => $helper->__('Are you sure you want to delete this tag?'),
                ),
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'id',
            'is_system' => true,
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('tag_ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('harper_tagger')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('harper_tagger')->__('Are you sure?'),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
