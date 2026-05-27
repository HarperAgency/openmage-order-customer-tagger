<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules_Grid
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('harperTaggerRulesGrid');
        $this->setDefaultSort('priority');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('harper_tagger/rule')->getCollection();
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

        $this->addColumn('label', array(
            'header' => $helper->__('Label'),
            'align'  => 'left',
            'index'  => 'label',
        ));

        $this->addColumn('tag_id', array(
            'header'   => $helper->__('Tag'),
            'align'    => 'left',
            'width'    => '140px',
            'index'    => 'tag_id',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_Badge',
        ));

        $this->addColumn('rule_trigger', array(
            'header'  => $helper->__('Trigger'),
            'align'   => 'left',
            'width'   => '120px',
            'index'   => 'rule_trigger',
            'type'    => 'options',
            'options' => array(
                'order_placed' => $helper->__('Order Placed'),
            ),
        ));

        $this->addColumn('operator', array(
            'header'  => $helper->__('Logic'),
            'align'   => 'center',
            'width'   => '60px',
            'index'   => 'operator',
            'type'    => 'options',
            'options' => array(
                'AND' => $helper->__('AND'),
                'OR'  => $helper->__('OR'),
            ),
        ));

        $this->addColumn('priority', array(
            'header' => $helper->__('Priority'),
            'align'  => 'center',
            'width'  => '70px',
            'index'  => 'priority',
            'type'   => 'number',
        ));

        $this->addColumn('is_active', array(
            'header'  => $helper->__('Active'),
            'align'   => 'center',
            'width'   => '70px',
            'index'   => 'is_active',
            'type'    => 'options',
            'options' => array(
                0 => $helper->__('No'),
                1 => $helper->__('Yes'),
            ),
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
                    'confirm' => $helper->__('Are you sure you want to delete this rule?'),
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
        $this->getMassactionBlock()->setFormFieldName('rule_ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('harper_tagger')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('harper_tagger')->__('Are you sure?'),
        ));

        $this->getMassactionBlock()->addItem('enable', array(
            'label' => Mage::helper('harper_tagger')->__('Enable'),
            'url'   => $this->getUrl('*/*/massEnable'),
        ));

        $this->getMassactionBlock()->addItem('disable', array(
            'label' => Mage::helper('harper_tagger')->__('Disable'),
            'url'   => $this->getUrl('*/*/massDisable'),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
