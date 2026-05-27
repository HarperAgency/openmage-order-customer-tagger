<?php
/**
 * HarperAgency_OrderCustomerTagger_Adminhtml_RulesController
 *
 * CRUD controller for rule management under Sales → Order & Customer Rules.
 */
class HarperAgency_OrderCustomerTagger_Adminhtml_RulesController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('sales/harper_tagger');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/harper_tagger')
            ->_addBreadcrumb(
                Mage::helper('harper_tagger')->__('Order & Customer Rules'),
                Mage::helper('harper_tagger')->__('Order & Customer Rules')
            );
        return $this;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    // ── New ───────────────────────────────────────────────────────────────────

    public function newAction()
    {
        $this->_forward('edit');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function editAction()
    {
        $id    = (int) $this->getRequest()->getParam('id');
        $model = Mage::getModel('harper_tagger/rule');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('harper_tagger')->__('Rule no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $formData = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($formData)) {
            $model->setData($formData);
        }

        Mage::register('harper_tagger_rule', $model);

        $this->_initAction();
        $this->_addBreadcrumb(
            $id
                ? Mage::helper('harper_tagger')->__('Edit Rule')
                : Mage::helper('harper_tagger')->__('New Rule'),
            $id
                ? Mage::helper('harper_tagger')->__('Edit Rule')
                : Mage::helper('harper_tagger')->__('New Rule')
        );

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent(
            $this->getLayout()->createBlock('harper_tagger/adminhtml_rules_edit')
        );

        $this->renderLayout();
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if (!$data) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('No data received.'));
            $this->_redirect('*/*/');
            return;
        }

        $id    = (int) $this->getRequest()->getParam('id');
        $model = Mage::getModel('harper_tagger/rule');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('harper_tagger')->__('Rule no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        // Validate and sanitise conditions JSON
        $conditionsRaw = isset($data['conditions']) ? $data['conditions'] : '[]';
        $conditions    = json_decode($conditionsRaw, true);
        if (!is_array($conditions)) {
            $conditions = array();
        }

        // Sanitise each condition
        $clean = array();
        foreach ($conditions as $c) {
            if (!is_array($c)) {
                continue;
            }
            $field    = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($c['field']    ?? '')));
            $operator = preg_replace('/[^a-z_]/',    '', strtolower((string) ($c['operator'] ?? '')));
            $value    = isset($c['value']) ? $c['value'] : '';

            if ($field === '' || $operator === '') {
                continue;
            }

            // Convert comma-separated in/not_in to array
            if (in_array($operator, array('in', 'not_in'), true) && is_string($value)) {
                $value = array_values(array_filter(array_map('trim', explode(',', $value))));
            }

            $clean[] = array(
                'field'    => $field,
                'operator' => $operator,
                'value'    => $value,
            );
        }

        $data['conditions'] = json_encode($clean);
        $data['is_active']  = isset($data['is_active']) ? (int) $data['is_active'] : 0;
        $data['priority']   = isset($data['priority'])  ? (int) $data['priority']  : 10;
        $data['operator']   = in_array(strtoupper((string) ($data['operator'] ?? '')), array('AND', 'OR'))
            ? strtoupper($data['operator'])
            : 'AND';

        try {
            $model->addData($data);
            $model->save();

            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('harper_tagger')->__('Rule was successfully saved.'));
            Mage::getSingleton('adminhtml/session')->setFormData(false);

            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            $this->_redirect('*/*/');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*/edit', array('id' => $id ?: $model->getId()));
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteAction()
    {
        $id    = (int) $this->getRequest()->getParam('id');
        $model = Mage::getModel('harper_tagger/rule')->load($id);

        if (!$model->getId()) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Rule no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('harper_tagger')->__('Rule was successfully deleted.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    // ── Mass delete ───────────────────────────────────────────────────────────

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('rule_ids');
        if (!is_array($ids) || empty($ids)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Please select at least one rule.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('harper_tagger/rule')->load((int) $id);
                if ($model->getId()) {
                    $model->delete();
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d rule(s) were successfully deleted.',
                    count($ids)
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    // ── Mass enable ───────────────────────────────────────────────────────────

    public function massEnableAction()
    {
        $this->_massSetActive(1);
    }

    // ── Mass disable ──────────────────────────────────────────────────────────

    public function massDisableAction()
    {
        $this->_massSetActive(0);
    }

    protected function _massSetActive($flag)
    {
        $ids = $this->getRequest()->getParam('rule_ids');
        if (!is_array($ids) || empty($ids)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Please select at least one rule.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('harper_tagger/rule')->load((int) $id);
                if ($model->getId()) {
                    $model->setIsActive((int) $flag)->save();
                }
            }
            $label = $flag ? 'enabled' : 'disabled';
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d rule(s) were ' . $label . '.',
                    count($ids)
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}
