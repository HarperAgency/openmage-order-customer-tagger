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
            // Return a generic stub for unregistered model aliases so that
            // calling getCollection() on them doesn't crash.
            return new class($alias) {
                private $alias;
                public function __construct($alias) { $this->alias = $alias; }
                public function getCollection() { return new StubCollection(); }
                public function load($id) { return $this; }
                public function getId() { return null; }
                public function getSlug() { return ''; }
                public function getType() { return ''; }
                public function getName() { return ''; }
                public function getColor() { return ''; }
                public function getCreatedAt() { return ''; }
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

// ── Load module ContextBuilder ────────────────────────────────────────────────

require_once dirname(__DIR__)
    . '/app/code/community/HarperAgency/OrderCustomerTagger/Model/RuleEngine/ContextBuilder.php';
