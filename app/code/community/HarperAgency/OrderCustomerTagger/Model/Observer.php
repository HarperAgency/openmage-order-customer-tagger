<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_Observer
 *
 * Hooks into sales_order_save_after.
 * Builds order/customer context, runs the rule engine, persists matched tags.
 */
class HarperAgency_OrderCustomerTagger_Model_Observer
{
    /**
     * Entry point — called by Magento event system after every order save.
     *
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function onOrderSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        try {
            HarperAgency_OrderCustomerTagger_Helper_Data::loadAutoloader();
            $this->_processOrder($order);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    // ── Order tagging ─────────────────────────────────────────────────────────

    /**
     * @param  Mage_Sales_Model_Order $order
     * @return void
     */
    protected function _processOrder(Mage_Sales_Model_Order $order)
    {
        $helper  = Mage::helper('harper_tagger');
        $builder = new HarperAgency_OrderCustomerTagger_Model_RuleEngine_ContextBuilder();

        // ── Order rules ───────────────────────────────────────────────────────
        $orderContext = $builder->fromOrder($order);
        $orderRules   = $helper->getActiveRules('order_placed');
        $orderRuleObjs = $this->_buildRuleObjects($orderRules, 'order');

        if (!empty($orderRuleObjs)) {
            $engine     = new \HarperAgency\RuleEngine\Engine($orderRuleObjs);
            $matchedIds = $engine->evaluate($orderContext);
            $this->_saveOrderTags((int) $order->getId(), $matchedIds);
        }

        // ── Customer rules ────────────────────────────────────────────────────
        $customerId = (int) $order->getCustomerId();
        if ($customerId > 0) {
            $customerContext  = $builder->fromCustomerId($customerId);
            $customerRules    = $helper->getActiveRules('order_placed');
            $customerRuleObjs = $this->_buildRuleObjects($customerRules, 'customer');

            if (!empty($customerRuleObjs)) {
                $engine     = new \HarperAgency\RuleEngine\Engine($customerRuleObjs);
                $matchedIds = $engine->evaluate($customerContext);
                $this->_saveCustomerTags($customerId, $matchedIds);
            }
        }
    }

    // ── Rule building ─────────────────────────────────────────────────────────

    /**
     * Convert a collection of DB rule models into RuleEngine Rule objects,
     * filtering to only rules whose tag matches the given type.
     *
     * @param  HarperAgency_OrderCustomerTagger_Model_Rule[] $ruleModels
     * @param  string $tagType  'order' or 'customer'
     * @return \HarperAgency\RuleEngine\Rule[]
     */
    protected function _buildRuleObjects($ruleModels, $tagType)
    {
        $ruleObjects = array();

        foreach ($ruleModels as $ruleModel) {
            $tagId = (int) $ruleModel->getTagId();
            $tag   = Mage::getModel('harper_tagger/tag')->load($tagId);

            if (!$tag->getId()) {
                continue;
            }
            if ((string) $tag->getType() !== $tagType) {
                continue;
            }

            $conditionsData = $ruleModel->getConditionsArray();
            $conditions     = array();

            foreach ($conditionsData as $cond) {
                if (empty($cond['field']) || empty($cond['operator'])) {
                    continue;
                }
                $conditions[] = new \HarperAgency\RuleEngine\Condition(
                    (string) $cond['field'],
                    (string) $cond['operator'],
                    isset($cond['value']) ? $cond['value'] : null
                );
            }

            if (empty($conditions)) {
                continue;
            }

            $operator = strtoupper((string) $ruleModel->getOperator());
            if (!in_array($operator, array('AND', 'OR'), true)) {
                $operator = 'AND';
            }

            $ruleObjects[] = new \HarperAgency\RuleEngine\Rule(
                $conditions,
                $operator,
                $tagId
            );
        }

        return $ruleObjects;
    }

    // ── Persistence ───────────────────────────────────────────────────────────

    /**
     * Insert order-tag associations, ignoring duplicates.
     *
     * @param  int   $orderId
     * @param  int[] $tagIds
     * @return void
     */
    protected function _saveOrderTags($orderId, array $tagIds)
    {
        foreach ($tagIds as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId <= 0) {
                continue;
            }

            // Check for existing association to honour the UNIQUE KEY
            $collection = Mage::getModel('harper_tagger/order_tag')->getCollection();
            $collection->addFieldToFilter('order_id', $orderId);
            $collection->addFieldToFilter('tag_id',   $tagId);

            if ($collection->getSize() > 0) {
                continue;
            }

            $model = Mage::getModel('harper_tagger/order_tag');
            $model->setData(array(
                'order_id' => $orderId,
                'tag_id'   => $tagId,
                'source'   => 'rule',
            ));
            $model->save();
        }
    }

    // ── Admin grid columns + filters ─────────────────────────────────────────

    /**
     * Inject a Tags column (with filter dropdown) into the sales orders grid
     * and the customers grid.  Also adds a "Re-run Tagger Rules" mass action
     * on the orders grid.
     *
     * Fired by the core_block_abstract_prepare_layout_after event.
     *
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function onBlockPrepareLayoutAfter(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            $tagOptions = $this->_buildTagOptions('order');

            $block->addColumnAfter('harper_tags', array(
                'header'                    => Mage::helper('harper_tagger')->__('Tags'),
                'align'                     => 'left',
                'index'                     => 'entity_id',
                'type'                      => 'options',
                'options'                   => $tagOptions,
                'sortable'                  => false,
                'renderer'                  => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_TagsList',
                'entity_type'               => 'order',
                'width'                     => '160px',
                'filter_condition_callback' => array($this, 'filterOrdersByTag'),
            ), 'grand_total');

            // Mass action: re-run rules on selected orders
            $block->getMassactionBlock()->addItem('harper_rerun_rules', array(
                'label'   => Mage::helper('harper_tagger')->__('Re-run Tagger Rules'),
                'url'     => Mage::helper('adminhtml')->getUrl('harper_tagger/adminhtml_orders/massRerunRules'),
                'confirm' => Mage::helper('harper_tagger')->__('Re-run tagger rules on selected orders?'),
            ));
        }

        if ($block instanceof Mage_Adminhtml_Block_Customer_Grid) {
            $tagOptions = $this->_buildTagOptions('customer');

            $block->addColumnAfter('harper_tags', array(
                'header'                    => Mage::helper('harper_tagger')->__('Tags'),
                'align'                     => 'left',
                'index'                     => 'entity_id',
                'type'                      => 'options',
                'options'                   => $tagOptions,
                'sortable'                  => false,
                'renderer'                  => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_TagsList',
                'entity_type'               => 'customer',
                'width'                     => '160px',
                'filter_condition_callback' => array($this, 'filterCustomersByTag'),
            ), 'email');
        }
    }

    /**
     * Filter condition callback for the Tags column on the orders grid.
     * Adds an EXISTS subquery when a tag filter value is selected.
     *
     * @param  Mage_Sales_Model_Resource_Order_Grid_Collection $collection
     * @param  Mage_Adminhtml_Block_Widget_Grid_Column         $column
     * @return $this
     */
    public function filterOrdersByTag($collection, $column)
    {
        $tagId = (int) $column->getFilter()->getValue();
        if (!$tagId) {
            return $this;
        }

        $tableName = Mage::getSingleton('core/resource')
            ->getTableName('harper_tagger/order_tag');

        $collection->getSelect()->where(
            'EXISTS (SELECT 1 FROM `' . $tableName . '` ht'
            . ' WHERE ht.order_id = main_table.entity_id'
            . ' AND ht.tag_id = ' . $tagId . ')'
        );

        return $this;
    }

    /**
     * Filter condition callback for the Tags column on the customers grid.
     *
     * @param  Mage_Customer_Model_Resource_Customer_Collection $collection
     * @param  Mage_Adminhtml_Block_Widget_Grid_Column          $column
     * @return $this
     */
    public function filterCustomersByTag($collection, $column)
    {
        $tagId = (int) $column->getFilter()->getValue();
        if (!$tagId) {
            return $this;
        }

        $tableName = Mage::getSingleton('core/resource')
            ->getTableName('harper_tagger/customer_tag');

        $collection->getSelect()->where(
            'EXISTS (SELECT 1 FROM `' . $tableName . '` ht'
            . ' WHERE ht.customer_id = main_table.entity_id'
            . ' AND ht.tag_id = ' . $tagId . ')'
        );

        return $this;
    }

    /**
     * Public wrapper so the Orders mass-action controller can re-use
     * the processing pipeline without duplicating logic.
     *
     * @param  Mage_Sales_Model_Order $order
     * @return void
     */
    public function processOrder(Mage_Sales_Model_Order $order)
    {
        HarperAgency_OrderCustomerTagger_Helper_Data::loadAutoloader();
        $this->_processOrder($order);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build an options array for the grid filter select.
     *
     * @param  string $type  'order' or 'customer'
     * @return array<int|string, string>
     */
    protected function _buildTagOptions($type)
    {
        $options    = array('' => Mage::helper('harper_tagger')->__('-- Any --'));
        $collection = Mage::getModel('harper_tagger/tag')->getCollection();
        $collection->addFieldToFilter('type', $type);
        $collection->setOrder('name', 'ASC');
        foreach ($collection as $tag) {
            $options[(int) $tag->getId()] = $tag->getName();
        }
        return $options;
    }

    /**
     * Insert customer-tag associations, ignoring duplicates.
     *
     * @param  int   $customerId
     * @param  int[] $tagIds
     * @return void
     */
    protected function _saveCustomerTags($customerId, array $tagIds)
    {
        foreach ($tagIds as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId <= 0) {
                continue;
            }

            $collection = Mage::getModel('harper_tagger/customer_tag')->getCollection();
            $collection->addFieldToFilter('customer_id', $customerId);
            $collection->addFieldToFilter('tag_id',      $tagId);

            if ($collection->getSize() > 0) {
                continue;
            }

            $model = Mage::getModel('harper_tagger/customer_tag');
            $model->setData(array(
                'customer_id' => $customerId,
                'tag_id'      => $tagId,
                'source'      => 'rule',
            ));
            $model->save();
        }
    }
}
