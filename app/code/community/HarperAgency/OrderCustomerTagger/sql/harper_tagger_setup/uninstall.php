<?php
/**
 * HarperAgency_OrderCustomerTagger — uninstall script.
 *
 * Drops all four module tables in dependency order (child → parent).
 * Runs when the module is removed via Magento's downgrade mechanism.
 */

/** @var Mage_Core_Model_Resource_Setup $this */
$installer = $this;
$installer->startSetup();

$conn = $installer->getConnection();

$conn->dropTable($installer->getTable('harper_tagger/order_tag'));
$conn->dropTable($installer->getTable('harper_tagger/customer_tag'));
$conn->dropTable($installer->getTable('harper_tagger/rule'));
$conn->dropTable($installer->getTable('harper_tagger/tag'));

$installer->endSetup();
