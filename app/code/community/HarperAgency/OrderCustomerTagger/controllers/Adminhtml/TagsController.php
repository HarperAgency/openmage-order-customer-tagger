<?php
/**
 * HarperAgency_OrderCustomerTagger_Adminhtml_TagsController
 *
 * CRUD controller for tag management under Sales → Order & Customer Tags.
 */
class HarperAgency_OrderCustomerTagger_Adminhtml_TagsController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * ACL resource check — requires Sales > Harper Tagger permission.
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('sales/harper_tagger');
    }

    /**
     * Initialise action — load layout and set active menu item.
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/harper_tagger')
            ->_addBreadcrumb(
                Mage::helper('harper_tagger')->__('Order & Customer Tags'),
                Mage::helper('harper_tagger')->__('Order & Customer Tags')
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
        $model = Mage::getModel('harper_tagger/tag');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('harper_tagger')->__('Tag no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        // Restore form data on validation failure
        $formData = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($formData)) {
            $model->setData($formData);
        }

        Mage::register('harper_tagger_tag', $model);

        $this->_initAction();
        $this->_addBreadcrumb(
            $id
                ? Mage::helper('harper_tagger')->__('Edit Tag')
                : Mage::helper('harper_tagger')->__('New Tag'),
            $id
                ? Mage::helper('harper_tagger')->__('Edit Tag')
                : Mage::helper('harper_tagger')->__('New Tag')
        );

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent(
            $this->getLayout()->createBlock('harper_tagger/adminhtml_tags_edit')
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
        $model = Mage::getModel('harper_tagger/tag');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('harper_tagger')->__('Tag no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        try {
            $model->addData($data);
            $model->save();

            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('harper_tagger')->__('Tag was successfully saved.'));
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
        $model = Mage::getModel('harper_tagger/tag')->load($id);

        if (!$model->getId()) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Tag no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('harper_tagger')->__('Tag was successfully deleted.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    // ── Mass delete ───────────────────────────────────────────────────────────

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('tag_ids');
        if (!is_array($ids) || empty($ids)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('harper_tagger')->__('Please select at least one tag.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('harper_tagger/tag')->load((int) $id);
                if ($model->getId()) {
                    $model->delete();
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d tag(s) were successfully deleted.',
                    count($ids)
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}
