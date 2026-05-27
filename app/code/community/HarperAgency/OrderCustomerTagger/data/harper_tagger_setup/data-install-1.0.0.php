<?php
/**
 * Seed default tags and rules on first install.
 * Mirrors WC plugin Seeder.php — 14 tags total (10 order, 4 customer).
 */

$installer = $this;
$installer->startSetup();

$conn      = $installer->getConnection();
$tagsTable = $installer->getTable('harper_tagger/tag');
$rulesTable = $installer->getTable('harper_tagger/rule');

$defaultTags = array(

    // ── ORDER TAGS ────────────────────────────────────────────────────────────

    array(
        'name'  => 'High Value',
        'slug'  => 'high-value',
        'color' => '#e67e22',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Order total over $500',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'order_total', 'operator' => 'gt', 'value' => 500),
            ),
        )),
    ),

    array(
        'name'  => 'Unconfirmed Payment',
        'slug'  => 'unconfirmed-payment',
        'color' => '#e74c3c',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Payment pending or on hold',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'payment_status', 'operator' => 'in',
                      'value' => array('pending', 'on-hold')),
            ),
        )),
    ),

    array(
        'name'  => 'Address Mismatch',
        'slug'  => 'address-mismatch',
        'color' => '#c0392b',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Billing address differs from shipping address',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'address_mismatch', 'operator' => 'is_true', 'value' => true),
            ),
        )),
    ),

    array(
        'name'  => 'Guest Checkout',
        'slug'  => 'guest-checkout',
        'color' => '#7f8c8d',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Order placed without an account',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'is_guest', 'operator' => 'is_true', 'value' => true),
            ),
        )),
    ),

    array(
        'name'  => 'First Order',
        'slug'  => 'first-order',
        'color' => '#3498db',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => "Customer's first completed order",
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'is_first_order', 'operator' => 'is_true', 'value' => true),
            ),
        )),
    ),

    array(
        'name'  => 'Has Coupon',
        'slug'  => 'has-coupon',
        'color' => '#1abc9c',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Discount coupon applied',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'has_coupon', 'operator' => 'is_true', 'value' => true),
            ),
        )),
    ),

    array(
        'name'  => 'Bulk Order',
        'slug'  => 'bulk',
        'color' => '#2980b9',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => '10 or more items',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'item_count', 'operator' => 'gte', 'value' => 10),
            ),
        )),
    ),

    array(
        'name'  => 'Local Pickup',
        'slug'  => 'local-pickup',
        'color' => '#27ae60',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Shipping method is local pickup',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'shipping_method', 'operator' => 'contains', 'value' => 'local_pickup'),
            ),
        )),
    ),

    array(
        'name'  => 'Free Shipping',
        'slug'  => 'free-shipping',
        'color' => '#16a085',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Free shipping applied',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'shipping_method', 'operator' => 'contains', 'value' => 'free_shipping'),
            ),
        )),
    ),

    array(
        'name'  => 'International',
        'slug'  => 'international',
        'color' => '#8e44ad',
        'type'  => 'order',
        'rules' => array(array(
            'label'      => 'Shipping outside US and Canada',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'shipping_country', 'operator' => 'not_in', 'value' => array('US', 'CA')),
            ),
        )),
    ),

    // ── CUSTOMER TAGS ─────────────────────────────────────────────────────────

    array(
        'name'  => 'VIP',
        'slug'  => 'vip',
        'color' => '#f1c40f',
        'type'  => 'customer',
        'rules' => array(array(
            'label'      => 'High LTV and frequent buyer',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'customer_ltv',         'operator' => 'gt',  'value' => 1000),
                array('field' => 'customer_order_count', 'operator' => 'gte', 'value' => 5),
            ),
        )),
    ),

    array(
        'name'  => 'Repeat Buyer',
        'slug'  => 'repeat-buyer',
        'color' => '#2ecc71',
        'type'  => 'customer',
        'rules' => array(array(
            'label'      => '3 or more completed orders',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'customer_order_count', 'operator' => 'gte', 'value' => 3),
            ),
        )),
    ),

    array(
        'name'  => 'At Risk',
        'slug'  => 'at-risk',
        'color' => '#e67e22',
        'type'  => 'customer',
        'rules' => array(array(
            'label'      => 'No order in 90+ days',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'days_since_last_order', 'operator' => 'gt',  'value' => 90),
                array('field' => 'customer_order_count',  'operator' => 'gte', 'value' => 2),
            ),
        )),
    ),

    array(
        'name'  => 'New Customer',
        'slug'  => 'new-customer',
        'color' => '#3498db',
        'type'  => 'customer',
        'rules' => array(array(
            'label'      => 'Exactly 1 completed order',
            'trigger'    => 'order_placed',
            'operator'   => 'AND',
            'priority'   => 10,
            'conditions' => array(
                array('field' => 'customer_order_count', 'operator' => 'eq', 'value' => 1),
            ),
        )),
    ),

);

foreach ($defaultTags as $tag) {
    $conn->insert($tagsTable, array(
        'name'      => $tag['name'],
        'slug'      => $tag['slug'],
        'color'     => $tag['color'],
        'type'      => $tag['type'],
        'image_url' => null,
    ));
    $tagId = (int) $conn->lastInsertId();

    foreach ($tag['rules'] as $rule) {
        $conn->insert($rulesTable, array(
            'tag_id'       => $tagId,
            'label'        => $rule['label'],
            'rule_trigger' => $rule['trigger'],
            'operator'     => $rule['operator'],
            'conditions'   => json_encode($rule['conditions']),
            'priority'     => $rule['priority'],
            'is_active'    => 1,
        ));
    }
}

$installer->endSetup();
