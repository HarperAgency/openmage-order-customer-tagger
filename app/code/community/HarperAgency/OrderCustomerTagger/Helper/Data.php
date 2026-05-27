<?php
/**
 * HarperAgency_OrderCustomerTagger_Helper_Data
 *
 * General helper — loads composer autoloader so RuleEngine classes are available,
 * and provides convenience methods for tag and rule loading.
 */
class HarperAgency_OrderCustomerTagger_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** @var bool */
    private static $_autoloaderLoaded = false;

    /**
     * Ensure vendor/autoload.php is loaded so the rule engine is available.
     * Called lazily by Observer and any code that uses RuleEngine classes.
     */
    public static function loadAutoloader()
    {
        if (self::$_autoloaderLoaded) {
            return;
        }
        // Module sits at vendor/harperagency/openmage-order-customer-tagger/
        // vendor/autoload.php is four directories up from this file.
        $autoloader = dirname(__FILE__, 4) . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
        self::$_autoloaderLoaded = true;
    }

    /**
     * Return all active rules for a given trigger, as an array of raw DB rows.
     *
     * @param  string $trigger  e.g. 'order_placed'
     * @return array
     */
    public function getActiveRules($trigger = 'order_placed')
    {
        $collection = Mage::getModel('harper_tagger/rule')->getCollection();
        $collection->addFieldToFilter('is_active',    1);
        $collection->addFieldToFilter('rule_trigger', $trigger);
        $collection->setOrder('priority', 'ASC');
        return $collection->getItems();
    }

    /**
     * Load a tag model by ID.
     *
     * @param  int $tagId
     * @return HarperAgency_OrderCustomerTagger_Model_Tag
     */
    public function getTag($tagId)
    {
        return Mage::getModel('harper_tagger/tag')->load($tagId);
    }

    /**
     * Return all tag models keyed by ID.
     *
     * @return array<int, HarperAgency_OrderCustomerTagger_Model_Tag>
     */
    public function getAllTagsById()
    {
        $collection = Mage::getModel('harper_tagger/tag')->getCollection();
        $result     = array();
        foreach ($collection as $tag) {
            $result[(int) $tag->getId()] = $tag;
        }
        return $result;
    }
}
