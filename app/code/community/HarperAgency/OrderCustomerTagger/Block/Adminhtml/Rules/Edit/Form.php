<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules_Edit_Form
 *
 * Rule edit form.  Includes a JS-driven condition builder that serialises
 * conditions to JSON in a hidden field on submit.
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Rules_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    // ── Context field catalogue ───────────────────────────────────────────────

    /**
     * Fields available in the order context, grouped by type.
     * Types: numeric, string, boolean
     */
    protected static $_orderFields = array(
        'order_total'      => array('label' => 'Order Total',        'type' => 'numeric'),
        'item_count'       => array('label' => 'Item Count',         'type' => 'numeric'),
        'shipping_method'  => array('label' => 'Shipping Method',    'type' => 'string'),
        'payment_method'   => array('label' => 'Payment Method',     'type' => 'string'),
        'payment_status'   => array('label' => 'Order Status',       'type' => 'string'),
        'shipping_country' => array('label' => 'Shipping Country',   'type' => 'string'),
        'billing_country'  => array('label' => 'Billing Country',    'type' => 'string'),
        'is_guest'         => array('label' => 'Is Guest',           'type' => 'boolean'),
        'is_first_order'   => array('label' => 'Is First Order',     'type' => 'boolean'),
        'has_coupon'       => array('label' => 'Has Coupon',         'type' => 'boolean'),
        'address_mismatch' => array('label' => 'Address Mismatch',   'type' => 'boolean'),
    );

    protected static $_customerFields = array(
        'customer_ltv'              => array('label' => 'Customer LTV',           'type' => 'numeric'),
        'customer_order_count'      => array('label' => 'Order Count',            'type' => 'numeric'),
        'customer_account_age_days' => array('label' => 'Account Age (days)',     'type' => 'numeric'),
        'days_since_last_order'     => array('label' => 'Days Since Last Order',  'type' => 'numeric'),
    );

    /** Operators available for each field type */
    protected static $_operatorsByType = array(
        'numeric' => array(
            'eq'  => '= equals',
            'neq' => '≠ not equals',
            'gt'  => '> greater than',
            'gte' => '≥ greater than or equal',
            'lt'  => '< less than',
            'lte' => '≤ less than or equal',
        ),
        'string' => array(
            'eq'          => '= equals',
            'neq'         => '≠ not equals',
            'contains'    => 'contains',
            'not_contains'=> 'does not contain',
            'in'          => 'in list (comma-separated)',
            'not_in'      => 'not in list',
        ),
        'boolean' => array(
            'is_true'  => 'is true',
            'is_false' => 'is false',
        ),
    );

    // ── Form preparation ──────────────────────────────────────────────────────

    protected function _prepareForm()
    {
        $rule   = Mage::registry('harper_tagger_rule');
        $helper = Mage::helper('harper_tagger');

        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post',
        ));

        $form->setHtmlIdPrefix('rule_');

        // ── Basic info ────────────────────────────────────────────────────────

        $basicFieldset = $form->addFieldset('basic_fieldset', array(
            'legend' => $helper->__('Rule Information'),
            'class'  => 'fieldset-wide',
        ));

        if ($rule && $rule->getId()) {
            $basicFieldset->addField('id', 'hidden', array('name' => 'id'));
        }

        $basicFieldset->addField('label', 'text', array(
            'name'     => 'label',
            'label'    => $helper->__('Label'),
            'title'    => $helper->__('A short descriptive name for this rule'),
            'required' => true,
        ));

        // Tag select
        $tagOptions = array(array('value' => '', 'label' => $helper->__('-- Select Tag --')));
        $tagCollection = Mage::getModel('harper_tagger/tag')->getCollection();
        foreach ($tagCollection as $tag) {
            $tagOptions[] = array(
                'value' => $tag->getId(),
                'label' => '[' . ucfirst($tag->getType()) . '] ' . $tag->getName(),
            );
        }

        $basicFieldset->addField('tag_id', 'select', array(
            'name'     => 'tag_id',
            'label'    => $helper->__('Tag to Apply'),
            'title'    => $helper->__('Tag applied to the order/customer when this rule matches'),
            'values'   => $tagOptions,
            'required' => true,
        ));

        $basicFieldset->addField('rule_trigger', 'select', array(
            'name'   => 'rule_trigger',
            'label'  => $helper->__('Trigger'),
            'values' => array(
                array('value' => 'order_placed', 'label' => $helper->__('Order Placed / Saved')),
            ),
        ));

        $basicFieldset->addField('operator', 'select', array(
            'name'   => 'operator',
            'label'  => $helper->__('Condition Logic'),
            'note'   => $helper->__('AND = all conditions must match; OR = any condition must match'),
            'values' => array(
                array('value' => 'AND', 'label' => $helper->__('AND — all conditions')),
                array('value' => 'OR',  'label' => $helper->__('OR — any condition')),
            ),
        ));

        $basicFieldset->addField('priority', 'text', array(
            'name'  => 'priority',
            'label' => $helper->__('Priority'),
            'note'  => $helper->__('Lower numbers run first (default 10)'),
            'class' => 'validate-digits',
        ));

        $basicFieldset->addField('is_active', 'select', array(
            'name'   => 'is_active',
            'label'  => $helper->__('Active'),
            'values' => array(
                array('value' => 1, 'label' => $helper->__('Yes')),
                array('value' => 0, 'label' => $helper->__('No')),
            ),
        ));

        // ── Conditions ────────────────────────────────────────────────────────

        $condFieldset = $form->addFieldset('conditions_fieldset', array(
            'legend' => $helper->__('Conditions'),
            'class'  => 'fieldset-wide',
        ));

        // Hidden JSON field — JS will keep this in sync
        $condFieldset->addField('conditions', 'hidden', array(
            'name' => 'conditions',
        ));

        // Placeholder for the JS-rendered condition builder
        $condFieldset->addField('conditions_builder_placeholder', 'note', array(
            'label' => $helper->__('Conditions'),
            'text'  => '<div id="ht-condition-builder"></div>'
                      . '<button type="button" id="ht-add-condition" class="scalable add">'
                      . '<span>' . $helper->__('Add Condition') . '</span></button>',
        ));

        // ── Set values ────────────────────────────────────────────────────────

        if ($rule) {
            $data = $rule->getData();
            // Store raw JSON so JS can parse it
            $data['conditions'] = $rule->getData('conditions') ?: '[]';
            if (!isset($data['priority'])) {
                $data['priority'] = 10;
            }
            $form->setValues($data);
        } else {
            $form->setValues(array('is_active' => 1, 'priority' => 10, 'operator' => 'AND', 'conditions' => '[]'));
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ── JS for condition builder ──────────────────────────────────────────────

    protected function _toHtml()
    {
        $html = parent::_toHtml();
        $html .= $this->_getConditionBuilderJs();
        return $html;
    }

    protected function _getConditionBuilderJs()
    {
        $orderFields    = self::$_orderFields;
        $customerFields = self::$_customerFields;
        $opsByType      = self::$_operatorsByType;

        // Build JS data structures
        $allFields = array();
        foreach ($orderFields as $k => $v) {
            $allFields[$k] = $v;
        }
        foreach ($customerFields as $k => $v) {
            $allFields[$k] = $v;
        }

        $fieldsJson  = Mage::helper('core')->jsonEncode($allFields);
        $opsJson     = Mage::helper('core')->jsonEncode($opsByType);

        return <<<HTML
<script type="text/javascript">
//<![CDATA[
(function () {
    var FIELDS     = {$fieldsJson};
    var OPS        = {$opsJson};
    var builder    = document.getElementById('ht-condition-builder');
    var hiddenJson = document.getElementById('rule_conditions');
    var addBtn     = document.getElementById('ht-add-condition');

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getFieldType(field) {
        return FIELDS[field] ? FIELDS[field].type : 'string';
    }

    function buildFieldSelect(selected) {
        var html = '<select class="ht-field select" style="width:160px">';
        for (var k in FIELDS) {
            var lbl = FIELDS[k].label;
            html += '<option value="' + k + '"' + (k === selected ? ' selected' : '') + '>' + lbl + '</option>';
        }
        html += '</select>';
        return html;
    }

    function buildOperatorSelect(fieldType, selectedOp) {
        var ops = OPS[fieldType] || OPS['string'];
        var html = '<select class="ht-op select" style="width:160px">';
        for (var op in ops) {
            html += '<option value="' + op + '"' + (op === selectedOp ? ' selected' : '') + '>' + ops[op] + '</option>';
        }
        html += '</select>';
        return html;
    }

    function buildValueInput(fieldType, value) {
        if (fieldType === 'boolean') {
            return '<span class="ht-value" style="color:#888;font-size:12px;">(no value needed)</span>'
                 + '<input type="hidden" class="ht-value-hidden" value="">';
        }
        return '<input type="text" class="ht-value input-text" style="width:140px" value="' + escAttr(String(value || '')) + '">';
    }

    function escAttr(s) {
        return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Row management ───────────────────────────────────────────────────────

    function addRow(field, op, value) {
        field = field || Object.keys(FIELDS)[0];
        var ftype = getFieldType(field);
        op    = op    || Object.keys(OPS[ftype] || OPS['string'])[0];
        value = (value !== undefined && value !== null) ? value : '';

        var row = document.createElement('div');
        row.className = 'ht-cond-row';
        row.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:6px;';
        row.innerHTML = buildFieldSelect(field)
            + buildOperatorSelect(ftype, op)
            + buildValueInput(ftype, value)
            + '<button type="button" class="ht-remove scalable delete" title="Remove">'
            + '<span>&#x2715;</span></button>';

        builder.appendChild(row);
        syncJson();

        // Field change → rebuild operator list and value input
        row.querySelector('.ht-field').addEventListener('change', function () {
            var newField = this.value;
            var newType  = getFieldType(newField);
            var opSel    = row.querySelector('.ht-op');
            var valWrap  = row.querySelector('.ht-value, .ht-value-hidden');

            // Replace op select
            var newOpHtml = buildOperatorSelect(newType, '');
            var tmpDiv = document.createElement('div');
            tmpDiv.innerHTML = newOpHtml;
            opSel.parentNode.replaceChild(tmpDiv.firstChild, opSel);

            // Replace value input
            var currentVal = valWrap ? valWrap.value : '';
            var newValHtml = buildValueInput(newType, currentVal);
            tmpDiv.innerHTML = newValHtml;
            var valContainer = row.querySelector('.ht-value, .ht-value-hidden');
            if (valContainer) {
                valContainer.parentNode.replaceChild(tmpDiv.firstChild, valContainer);
                // If boolean, also append hidden
                if (newType === 'boolean') {
                    tmpDiv2 = document.createElement('div');
                    tmpDiv2.innerHTML = buildValueInput(newType, '');
                    // already handled above — span+hidden
                }
            }

            row.querySelector('.ht-op').addEventListener('change', syncJson);
            var vi = row.querySelector('.ht-value, .ht-value-hidden');
            if (vi) vi.addEventListener('input', syncJson);
            syncJson();
        });

        row.querySelector('.ht-op').addEventListener('change', syncJson);
        var vi = row.querySelector('.ht-value, input.ht-value-hidden');
        if (vi) vi.addEventListener('input', syncJson);

        row.querySelector('.ht-remove').addEventListener('click', function () {
            builder.removeChild(row);
            syncJson();
        });
    }

    function syncJson() {
        var rows = builder.querySelectorAll('.ht-cond-row');
        var conditions = [];
        rows.forEach(function (row) {
            var field = row.querySelector('.ht-field').value;
            var op    = row.querySelector('.ht-op').value;
            var vi    = row.querySelector('input.ht-value, input.ht-value-hidden');
            var value = vi ? vi.value : '';
            conditions.push({ field: field, operator: op, value: value });
        });
        hiddenJson.value = JSON.stringify(conditions);
    }

    // ── Init: load existing conditions ───────────────────────────────────────

    function init() {
        var existing = [];
        try { existing = JSON.parse(hiddenJson.value || '[]'); } catch(e) {}
        if (Array.isArray(existing) && existing.length > 0) {
            existing.forEach(function (c) { addRow(c.field, c.operator, c.value); });
        }
        addBtn.addEventListener('click', function () { addRow(); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
//]]>
</script>
HTML;
    }
}
