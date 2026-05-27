<?php
/**
 * Install schema for HarperAgency_OrderCustomerTagger v1.0.0
 * Creates 4 tables: tags, rules, order_tags, customer_tags
 */

$installer = $this;
$installer->startSetup();

$conn = $installer->getConnection();

// ── harper_tagger_tags ────────────────────────────────────────────────────────

$tagsTable = $installer->getTable('harper_tagger/tag');

$conn->query("
CREATE TABLE {$tagsTable} (
    id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)        NOT NULL,
    slug       VARCHAR(100)        NOT NULL,
    color      VARCHAR(7)          NOT NULL DEFAULT '#3788d8',
    type       VARCHAR(10)         NOT NULL DEFAULT 'order',
    image_url  VARCHAR(500)                 DEFAULT NULL,
    created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Harper Tagger — tag definitions'
");

// ── harper_tagger_rules ───────────────────────────────────────────────────────

$rulesTable = $installer->getTable('harper_tagger/rule');

$conn->query("
CREATE TABLE {$rulesTable} (
    id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    tag_id       BIGINT(20) UNSIGNED NOT NULL,
    label        VARCHAR(100)        NOT NULL DEFAULT '',
    rule_trigger VARCHAR(30)         NOT NULL DEFAULT 'order_placed',
    operator     VARCHAR(3)          NOT NULL DEFAULT 'AND',
    conditions   LONGTEXT            NOT NULL,
    priority     SMALLINT UNSIGNED   NOT NULL DEFAULT 10,
    is_active    TINYINT(1)          NOT NULL DEFAULT 1,
    created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY tag_id             (tag_id),
    KEY is_active_trigger  (is_active, rule_trigger)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Harper Tagger — rule definitions'
");

// ── harper_tagger_order_tags ──────────────────────────────────────────────────

$orderTagsTable = $installer->getTable('harper_tagger/order_tag');

$conn->query("
CREATE TABLE {$orderTagsTable} (
    id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id   BIGINT(20) UNSIGNED NOT NULL,
    tag_id     BIGINT(20) UNSIGNED NOT NULL,
    source     VARCHAR(10)         NOT NULL DEFAULT 'rule',
    created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY order_tag (order_id, tag_id),
    KEY tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Harper Tagger — tags applied to orders'
");

// ── harper_tagger_customer_tags ───────────────────────────────────────────────

$customerTagsTable = $installer->getTable('harper_tagger/customer_tag');

$conn->query("
CREATE TABLE {$customerTagsTable} (
    id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT(20) UNSIGNED NOT NULL,
    tag_id      BIGINT(20) UNSIGNED NOT NULL,
    source      VARCHAR(10)         NOT NULL DEFAULT 'rule',
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY customer_tag (customer_id, tag_id),
    KEY tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Harper Tagger — tags applied to customers'
");

$installer->endSetup();
