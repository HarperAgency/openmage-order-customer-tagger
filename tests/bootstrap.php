<?php
/**
 * PHPUnit bootstrap for HarperAgency OpenMage Order & Customer Tagger unit tests.
 *
 * Does NOT load Magento — all Mage classes are stubbed below.
 * The RuleEngine (Condition, Rule, Engine) is pure PHP and loaded via
 * the path-repository vendor/autoload.php.
 */

// Load composer autoloader (includes harperagency/rule-engine)
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback: load directly from source repo so tests run without composer install
    $reSrc = dirname(__DIR__, 2) . '/harper-rule-engine/src';
    require_once $reSrc . '/Condition.php';
    require_once $reSrc . '/Rule.php';
    require_once $reSrc . '/Engine.php';
}

// ── StubCollection — minimal collection for _isFirstOrder / filter chains ─────

if (!class_exists('StubCollection')) {
    class StubCollection implements \Countable, \Iterator
    {
        private $_items = array();
        private $_position = 0;

        public function __construct(array $items = array())
        {
            $this->_items = array_values($items);
        }

        public function addFieldToFilter($field, $value = null) { return $this; }
        public function setPageSize($size) { return $this; }
        public function setOrder($field, $dir = 'ASC') { return $this; }
        public function getSize() { return count($this->_items); }
        public function getItems() { return $this->_items; }

        // Iterator
        public function current() { return $this->_items[$this->_position]; }
        public function key()     { return $this->_position; }
        public function next()    { $this->_position++; }
        public function rewind()  { $this->_position = 0; }
        public function valid()   { return isset($this->_items[$this->_position]); }

        // Countable
        public function count() { return count($this->_items); }
    }
}

// ── Minimal Mage stub ─────────────────────────────────────────────────────────

if (!class_exists('Mage')) {
    class Mage
    {
        private static $_registry = array();
        private static $_models   = array();

        public static function register($key, $value)
        {
            self::$_registry[$key] = $value;
        }

        public static function registry($key)
        {
            return isset(self::$_registry[$key]) ? self::$_registry[$key] : null;
        }

        public static function getModel($alias)
        {
            if (isset(self::$_models[$alias])) {
                return self::$_models[$alias];
            }
            // Return a stub model that supports load($id) with per-id registry lookup
            return new class($alias) extends Varien_Object {
                private $alias;
                public function __construct($a) { parent::__construct(); $this->alias = $a; }
                public function getCollection() {
                    // Check collection stubs registry
                    $map = Mage::registry('__collection_stubs') ?: array();
                    return $map[$this->alias] ?? new StubCollection();
                }
                public function load($id) {
                    // Check per-id model registry
                    $map = Mage::registry('__model_by_id') ?: array();
                    $key = "{$this->alias}:{$id}";
                    if (isset($map[$key])) {
                        // Copy data from registered stub into this instance
                        $stub = $map[$key];
                        if (method_exists($stub, 'getData')) {
                            $this->setData($stub->getData());
                        }
                    }
                    return $this;
                }
                public function getId()        { return $this->getData('id'); }
                public function getSlug()      { return (string) $this->getData('slug'); }
                public function getType()      { return (string) $this->getData('type'); }
                public function getName()      { return (string) $this->getData('name'); }
                public function getColor()     { return (string) $this->getData('color'); }
                public function getCreatedAt() { return (string) $this->getData('created_at'); }
                public function getConditionsArray() {
                    $raw = $this->getData('conditions');
                    if (is_array($raw)) return $raw;
                    return json_decode((string)($raw ?? '[]'), true) ?: array();
                }
                public function getTagId()    { return (int) $this->getData('tag_id'); }
                public function getOperator() { return (string) ($this->getData('operator') ?: 'AND'); }
                public function save()        {
                    $cbs = Mage::registry('__save_callbacks') ?: array();
                    if (isset($cbs[$this->alias])) { ($cbs[$this->alias])(); }
                    return $this;
                }
            };
        }

        /** Allow tests to inject model stubs. */
        public static function _setModel($alias, $obj)
        {
            self::$_models[$alias] = $obj;
        }

        public static function logException(Exception $e) {}
        public static function log($msg) {}

        public static function helper($alias)
        {
            return new class {
                public function __($text) { return $text; }
                public function getActiveRules($trigger = 'order_placed') { return array(); }
            };
        }

        public static function getSingleton($alias)
        {
            return new class {
                public function isAllowed($resource) { return true; }
                public function addSuccess($msg) {}
                public function addError($msg) {}
                public function setFormData($data) {}
                public function getFormData($clear = false) { return null; }
            };
        }
    }
}

// ── Varien_Object stub ────────────────────────────────────────────────────────

if (!class_exists('Varien_Object')) {
    class Varien_Object
    {
        protected $_data = array();

        public function __construct(array $data = array())
        {
            $this->_data = $data;
        }

        public function setData($key, $value = null)
        {
            if (is_array($key)) {
                $this->_data = $key;
            } else {
                $this->_data[$key] = $value;
            }
            return $this;
        }

        public function addData(array $data)
        {
            $this->_data = array_merge($this->_data, $data);
            return $this;
        }

        public function getData($key = null)
        {
            if ($key === null) {
                return $this->_data;
            }
            return isset($this->_data[$key]) ? $this->_data[$key] : null;
        }

        public function __call($method, $args)
        {
            if (strpos($method, 'get') === 0) {
                $key = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst(substr($method, 3))));
                return $this->getData($key);
            }
            if (strpos($method, 'set') === 0) {
                $key = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst(substr($method, 3))));
                return $this->setData($key, $args[0] ?? null);
            }
            return null;
        }
    }
}

// ── Mage_Core_Model_Abstract stub ─────────────────────────────────────────────

if (!class_exists('Mage_Core_Model_Abstract')) {
    class Mage_Core_Model_Abstract extends Varien_Object
    {
        public function getId()
        {
            return $this->getData('id');
        }

        public function load($id)
        {
            return $this;
        }

        public function save()
        {
            return $this;
        }

        public function delete()
        {
            return $this;
        }

        public function getCollection()
        {
            return new StubCollection();
        }

        public function getTagId()    { return (int) $this->getData('tag_id'); }
        public function getOperator() { return (string) ($this->getData('operator') ?: 'AND'); }
        public function getType()     { return (string) $this->getData('type'); }

        /**
         * Decode JSON-encoded conditions stored under 'conditions' key.
         */
        public function getConditionsArray(): array
        {
            $raw = $this->getData('conditions');
            if (is_array($raw)) return $raw;
            return json_decode((string)($raw ?? '[]'), true) ?: [];
        }

        protected function _construct() {}
    }
}

// ── Mage_Sales_Model_Order stub ───────────────────────────────────────────────

if (!class_exists('Mage_Sales_Model_Order')) {
    class Mage_Sales_Model_Order extends Mage_Core_Model_Abstract
    {
        private $_shippingAddress = null;
        private $_billingAddress  = null;
        private $_payment         = null;
        private $_items           = array();

        public function setShippingAddress($addr)  { $this->_shippingAddress = $addr; return $this; }
        public function setBillingAddress($addr)   { $this->_billingAddress  = $addr; return $this; }
        public function setPayment($payment)       { $this->_payment         = $payment; return $this; }
        public function setItems(array $items)     { $this->_items           = $items; return $this; }

        public function getShippingAddress() { return $this->_shippingAddress; }
        public function getBillingAddress()  { return $this->_billingAddress; }
        public function getPayment()         { return $this->_payment; }
        public function getAllVisibleItems() { return $this->_items; }

        public function getGrandTotal()  { return (float) $this->getData('grand_total'); }
        public function getStatus()      { return (string) $this->getData('status'); }
        public function getShippingMethod() { return (string) $this->getData('shipping_method'); }
        public function getCouponCode()  { return (string) $this->getData('coupon_code'); }
        public function getCustomerId()  { return (int)   $this->getData('customer_id'); }
    }
}

// ── Order address stub ────────────────────────────────────────────────────────

if (!class_exists('Mage_Sales_Model_Order_Address')) {
    class Mage_Sales_Model_Order_Address extends Varien_Object
    {
        public function getCountryId() { return (string) $this->getData('country_id'); }
        public function getPostcode()  { return (string) $this->getData('postcode'); }
    }
}

// ── Order item stub ───────────────────────────────────────────────────────────

if (!class_exists('Mage_Sales_Model_Order_Item')) {
    class Mage_Sales_Model_Order_Item extends Varien_Object
    {
        public function getQtyOrdered() { return (int) $this->getData('qty_ordered'); }
        public function getSku()        { return (string) $this->getData('sku'); }
    }
}

// ── Order payment stub ────────────────────────────────────────────────────────

if (!class_exists('Mage_Sales_Model_Order_Payment')) {
    class Mage_Sales_Model_Order_Payment extends Varien_Object
    {
        public function getMethod() { return (string) $this->getData('method'); }
    }
}

// ── Mage_Core_Helper_Abstract stub ───────────────────────────────────────────

if (!class_exists('Mage_Core_Helper_Abstract')) {
    class Mage_Core_Helper_Abstract
    {
        public function __($text) { return $text; }
    }
}

// ── Varien_Event and Varien_Event_Observer stubs ──────────────────────────────

if (!class_exists('Varien_Event')) {
    class Varien_Event extends Varien_Object
    {
        public function getOrder() { return $this->getData('order'); }
        public function setOrder($order) { $this->setData('order', $order); return $this; }
    }
}

if (!class_exists('Varien_Event_Observer')) {
    class Varien_Event_Observer extends Varien_Object
    {
        private $_event;
        public function __construct() { $this->_event = new Varien_Event(); }
        public function getEvent() { return $this->_event; }
    }
}

// ── Extended Mage stub helpers for Observer tests ─────────────────────────────

// Allow registering a specific model instance keyed by alias+id
// and registering collection stubs with save callbacks.
if (!method_exists('Mage', 'registerModel')) {
    class_alias('Mage', 'MageBase_DoNotUse');
}

// Patch Mage::getModel() to support per-id lookup and save callbacks
// We do this by storing extra maps in the static registry.
Mage::register('__model_by_id', array());
Mage::register('__save_callbacks', array());
Mage::register('__collection_stubs', array());

// ── Helper functions used in ObserverTest ─────────────────────────────────────

if (!function_exists('Mage_registerModel')) {
    /** Register a specific model instance returned when getModel($alias)->load($id). */
    function Mage_registerModel_byId(string $alias, object $obj, int $id): void {
        // Store in registry map
        $map = Mage::registry('__model_by_id') ?: array();
        $map["{$alias}:{$id}"] = $obj;
        Mage::register('__model_by_id', $map);
    }
}

// Extend Mage with registerModel convenience — add directly to existing class via registry
Mage::register('_registerModel_fn', function(string $alias, object $obj, int $id) {
    $map = Mage::registry('__model_by_id') ?: array();
    $map["{$alias}:{$id}"] = $obj;
    Mage::register('__model_by_id', $map);
});

Mage::register('_registerCollection_fn', function(string $alias, StubCollection $col) {
    $map = Mage::registry('__collection_stubs') ?: array();
    $map[$alias] = $col;
    Mage::register('__collection_stubs', $map);
});

Mage::register('_registerSaveCallback_fn', function(string $alias, callable $cb) {
    $map = Mage::registry('__save_callbacks') ?: array();
    $map[$alias] = $cb;
    Mage::register('__save_callbacks', $map);
});

// Provide global convenience functions for tests
function mage_test_register_model(string $alias, object $obj, int $id): void {
    $fn = Mage::registry('_registerModel_fn');
    $fn($alias, $obj, $id);
}
function mage_test_register_collection(string $alias, StubCollection $col): void {
    $fn = Mage::registry('_registerCollection_fn');
    $fn($alias, $col);
}

// ── Helper for Observer: Rule model getConditionsArray() ──────────────────────

if (!class_exists('Mage_Core_Model_Abstract') || !method_exists('Mage_Core_Model_Abstract', 'getConditionsArray')) {
    // Extend with a conditions helper used by Observer._buildRuleObjects
}

// ── Load module classes ───────────────────────────────────────────────────────

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/RuleEngine/ContextBuilder.php';

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/Observer.php';

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/Tag.php';

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/OrderTag.php';

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/CustomerTag.php';

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Helper/Data.php';
