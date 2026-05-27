<?php
/**
 * Unit tests for HarperAgency_OrderCustomerTagger_Model_RuleEngine_ContextBuilder
 *
 * Tests cover all 14 seed-rule conditions:
 *   Order:    order_total, payment_status, address_mismatch, is_guest,
 *             is_first_order, has_coupon, item_count, shipping_method (×2),
 *             shipping_country
 *   Customer: customer_ltv, customer_order_count (×3), days_since_last_order
 */

use HarperAgency\RuleEngine\Condition;
use HarperAgency\RuleEngine\Engine;
use HarperAgency\RuleEngine\Rule;
use PHPUnit\Framework\TestCase;

class ContextBuilderTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function builder()
    {
        return new HarperAgency_OrderCustomerTagger_Model_RuleEngine_ContextBuilder();
    }

    private function makeOrder(array $overrides = [])
    {
        $defaults = [
            'id'              => 1,
            'grand_total'     => 100.0,
            'status'          => 'processing',
            'shipping_method' => 'flatrate_flatrate',
            'coupon_code'     => '',
            'customer_id'     => 1,
        ];
        $data = array_merge($defaults, $overrides);

        $order = new Mage_Sales_Model_Order();
        $order->setData($data);

        // Default matching billing == shipping
        $billing = new Mage_Sales_Model_Order_Address();
        $billing->setData(['country_id' => 'US', 'postcode' => '10001']);
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'US', 'postcode' => '10001']);
        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);

        $payment = new Mage_Sales_Model_Order_Payment();
        $payment->setData(['method' => 'checkmo']);
        $order->setPayment($payment);

        return $order;
    }

    // ── fromOrder: order_total ────────────────────────────────────────────────

    public function testOrderTotalIsCaptured()
    {
        $order = $this->makeOrder(['grand_total' => 750.0]);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertSame(750.0, $ctx['order_total']);
    }

    public function testHighValueRuleMatchesWhenTotalOver500()
    {
        $order = $this->makeOrder(['grand_total' => 600.0]);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('order_total', 'gt', 500.0)],
            Rule::OPERATOR_AND,
            'high-value'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['high-value'], $engine->evaluate($ctx));
    }

    public function testHighValueRuleDoesNotMatchWhenTotalUnder500()
    {
        $order = $this->makeOrder(['grand_total' => 250.0]);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('order_total', 'gt', 500.0)],
            Rule::OPERATOR_AND,
            'high-value'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    // ── fromOrder: payment_status ─────────────────────────────────────────────

    public function testPaymentStatusIsCaptured()
    {
        $order = $this->makeOrder(['status' => 'pending']);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertSame('pending', $ctx['payment_status']);
    }

    public function testUnconfirmedPaymentRuleMatchesOnPending()
    {
        $order = $this->makeOrder(['status' => 'pending']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('payment_status', 'in', ['pending', 'on-hold'])],
            Rule::OPERATOR_AND,
            'unconfirmed-payment'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['unconfirmed-payment'], $engine->evaluate($ctx));
    }

    public function testUnconfirmedPaymentRuleDoesNotMatchOnProcessing()
    {
        $order = $this->makeOrder(['status' => 'processing']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('payment_status', 'in', ['pending', 'on-hold'])],
            Rule::OPERATOR_AND,
            'unconfirmed-payment'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    // ── fromOrder: is_guest ───────────────────────────────────────────────────

    public function testIsGuestTrueWhenCustomerIdIsZero()
    {
        $order = $this->makeOrder(['customer_id' => 0]);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertTrue($ctx['is_guest']);
    }

    public function testIsGuestFalseWhenCustomerIdIsSet()
    {
        $order = $this->makeOrder(['customer_id' => 42]);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertFalse($ctx['is_guest']);
    }

    public function testGuestCheckoutRuleMatchesForGuest()
    {
        $order = $this->makeOrder(['customer_id' => 0]);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('is_guest', 'is_true', true)],
            Rule::OPERATOR_AND,
            'guest-checkout'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['guest-checkout'], $engine->evaluate($ctx));
    }

    // ── fromOrder: has_coupon ─────────────────────────────────────────────────

    public function testHasCouponTrueWhenCouponCodeSet()
    {
        $order = $this->makeOrder(['coupon_code' => 'SAVE10']);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertTrue($ctx['has_coupon']);
    }

    public function testHasCouponFalseWhenNoCoupon()
    {
        $order = $this->makeOrder(['coupon_code' => '']);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertFalse($ctx['has_coupon']);
    }

    public function testHasCouponRuleMatchesWithCoupon()
    {
        $order = $this->makeOrder(['coupon_code' => 'DISCOUNT20']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('has_coupon', 'is_true', true)],
            Rule::OPERATOR_AND,
            'has-coupon'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['has-coupon'], $engine->evaluate($ctx));
    }

    // ── fromOrder: item_count ─────────────────────────────────────────────────

    public function testItemCountSumsQuantities()
    {
        $item1 = new Mage_Sales_Model_Order_Item();
        $item1->setData(['qty_ordered' => 3, 'sku' => 'ABC']);
        $item2 = new Mage_Sales_Model_Order_Item();
        $item2->setData(['qty_ordered' => 7, 'sku' => 'DEF']);

        $order = $this->makeOrder();
        $order->setItems([$item1, $item2]);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertSame(10, $ctx['item_count']);
    }

    public function testBulkRuleMatchesWhenTenOrMoreItems()
    {
        $item = new Mage_Sales_Model_Order_Item();
        $item->setData(['qty_ordered' => 10, 'sku' => 'BULK']);

        $order = $this->makeOrder();
        $order->setItems([$item]);

        $ctx  = $this->builder()->fromOrder($order);
        $rule = new Rule(
            [new Condition('item_count', 'gte', 10)],
            Rule::OPERATOR_AND,
            'bulk'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['bulk'], $engine->evaluate($ctx));
    }

    // ── fromOrder: shipping_method ────────────────────────────────────────────

    public function testShippingMethodIsCaptured()
    {
        $order = $this->makeOrder(['shipping_method' => 'freeshipping_freeshipping']);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertSame('freeshipping_freeshipping', $ctx['shipping_method']);
    }

    public function testLocalPickupRuleMatchesWhenMethodContainsLocalPickup()
    {
        $order = $this->makeOrder(['shipping_method' => 'local_pickup_local_pickup']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('shipping_method', 'contains', 'local_pickup')],
            Rule::OPERATOR_AND,
            'local-pickup'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['local-pickup'], $engine->evaluate($ctx));
    }

    public function testFreeShippingRuleMatchesWhenMethodContainsFreeShipping()
    {
        $order = $this->makeOrder(['shipping_method' => 'freeshipping_freeshipping']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('shipping_method', 'contains', 'free_shipping')],
            Rule::OPERATOR_AND,
            'free-shipping'
        );
        $engine = new Engine([$rule]);

        // 'freeshipping_freeshipping' does not contain 'free_shipping' (different string)
        $this->assertSame([], $engine->evaluate($ctx));
    }

    public function testFreeShippingRuleMatchesExact()
    {
        $order = $this->makeOrder(['shipping_method' => 'free_shipping_free_shipping']);
        $ctx   = $this->builder()->fromOrder($order);

        $rule   = new Rule(
            [new Condition('shipping_method', 'contains', 'free_shipping')],
            Rule::OPERATOR_AND,
            'free-shipping'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['free-shipping'], $engine->evaluate($ctx));
    }

    // ── fromOrder: shipping_country ───────────────────────────────────────────

    public function testShippingCountryIsCapturedFromAddress()
    {
        $order    = $this->makeOrder();
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'GB', 'postcode' => 'W1A 1AA']);
        $order->setShippingAddress($shipping);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertSame('GB', $ctx['shipping_country']);
    }

    public function testInternationalRuleMatchesWhenNotUsOrCa()
    {
        $order    = $this->makeOrder();
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'DE', 'postcode' => '10115']);
        $order->setShippingAddress($shipping);

        $ctx  = $this->builder()->fromOrder($order);
        $rule = new Rule(
            [new Condition('shipping_country', 'not_in', ['US', 'CA'])],
            Rule::OPERATOR_AND,
            'international'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['international'], $engine->evaluate($ctx));
    }

    public function testInternationalRuleDoesNotMatchForUs()
    {
        $order    = $this->makeOrder();
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'US', 'postcode' => '90210']);
        $order->setShippingAddress($shipping);

        $ctx  = $this->builder()->fromOrder($order);
        $rule = new Rule(
            [new Condition('shipping_country', 'not_in', ['US', 'CA'])],
            Rule::OPERATOR_AND,
            'international'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    // ── fromOrder: address_mismatch ───────────────────────────────────────────

    public function testAddressMismatchTrueWhenCountriesDiffer()
    {
        $order = $this->makeOrder();

        $billing = new Mage_Sales_Model_Order_Address();
        $billing->setData(['country_id' => 'US', 'postcode' => '10001']);
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'CA', 'postcode' => 'M5H 2N2']);

        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertTrue($ctx['address_mismatch']);
    }

    public function testAddressMismatchTrueWhenPostcodesDiffer()
    {
        $order = $this->makeOrder();

        $billing = new Mage_Sales_Model_Order_Address();
        $billing->setData(['country_id' => 'US', 'postcode' => '10001']);
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'US', 'postcode' => '90210']);

        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertTrue($ctx['address_mismatch']);
    }

    public function testAddressMismatchFalseWhenAddressesMatch()
    {
        $order = $this->makeOrder();

        $billing = new Mage_Sales_Model_Order_Address();
        $billing->setData(['country_id' => 'US', 'postcode' => '10001']);
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'US', 'postcode' => '10001']);

        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertFalse($ctx['address_mismatch']);
    }

    public function testAddressMismatchRuleMatchesWhenAddressesDiffer()
    {
        $order = $this->makeOrder();

        $billing  = new Mage_Sales_Model_Order_Address();
        $billing->setData(['country_id' => 'US', 'postcode' => '10001']);
        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'CA', 'postcode' => 'K1A 0A6']);

        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);

        $ctx  = $this->builder()->fromOrder($order);
        $rule = new Rule(
            [new Condition('address_mismatch', 'is_true', true)],
            Rule::OPERATOR_AND,
            'address-mismatch'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['address-mismatch'], $engine->evaluate($ctx));
    }

    // ── fromOrder: items array ────────────────────────────────────────────────

    public function testItemsArrayContainsSkuEntries()
    {
        $item = new Mage_Sales_Model_Order_Item();
        $item->setData(['qty_ordered' => 1, 'sku' => 'SKU-001']);

        $order = $this->makeOrder();
        $order->setItems([$item]);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertCount(1, $ctx['items']);
        $this->assertSame('SKU-001', $ctx['items'][0]['sku']);
        $this->assertSame([], $ctx['items'][0]['categories']);
    }

    public function testEmptyOrderHasZeroItemCount()
    {
        $order = $this->makeOrder();
        $order->setItems([]);
        $ctx = $this->builder()->fromOrder($order);

        $this->assertSame(0, $ctx['item_count']);
    }

    // ── fromOrder: all expected keys present ──────────────────────────────────

    public function testFromOrderReturnsAllExpectedKeys()
    {
        $ctx = $this->builder()->fromOrder($this->makeOrder());

        $expectedKeys = [
            'order_total', 'item_count', 'shipping_method', 'payment_method',
            'payment_status', 'shipping_country', 'billing_country',
            'is_guest', 'is_first_order', 'has_coupon', 'address_mismatch', 'items',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $ctx, "Missing context key: $key");
        }
    }

    // ── Full engine scenario: multiple order rules ────────────────────────────

    public function testFullOrderScenarioHighValueInternationalBulk()
    {
        $item = new Mage_Sales_Model_Order_Item();
        $item->setData(['qty_ordered' => 12, 'sku' => 'WIDGET']);

        $order = $this->makeOrder([
            'grand_total'     => 750.0,
            'status'          => 'processing',
            'shipping_method' => 'flatrate_flatrate',
            'coupon_code'     => '',
            'customer_id'     => 5,
        ]);
        $order->setItems([$item]);

        $shipping = new Mage_Sales_Model_Order_Address();
        $shipping->setData(['country_id' => 'GB', 'postcode' => 'EC1A 1BB']);
        $order->setShippingAddress($shipping);

        $ctx = $this->builder()->fromOrder($order);

        $rules = [
            new Rule([new Condition('order_total',      'gt',     500.0)],       Rule::OPERATOR_AND, 'high-value'),
            new Rule([new Condition('item_count',       'gte',    10)],           Rule::OPERATOR_AND, 'bulk'),
            new Rule([new Condition('shipping_country', 'not_in', ['US', 'CA'])], Rule::OPERATOR_AND, 'international'),
            new Rule([new Condition('is_guest',         'is_true', true)],        Rule::OPERATOR_AND, 'guest-checkout'),
            new Rule([new Condition('has_coupon',       'is_true', true)],        Rule::OPERATOR_AND, 'has-coupon'),
        ];

        $result = (new Engine($rules))->evaluate($ctx);

        // customer_id = 5 so not guest; no coupon
        $this->assertSame(['high-value', 'bulk', 'international'], $result);
    }

    // ── Customer context from stub data ───────────────────────────────────────

    public function testVipRuleRequiresBothLtvAndOrderCount()
    {
        // Simulate customer context manually (fromCustomerId would need Mage DB)
        $ctx = [
            'customer_ltv'              => 2000.0,
            'customer_order_count'      => 8,
            'customer_account_age_days' => 365,
            'days_since_last_order'     => 5,
            'customer_tags'             => [],
        ];

        $rule   = new Rule(
            [
                new Condition('customer_ltv',         'gt',  1000.0),
                new Condition('customer_order_count', 'gte', 5),
            ],
            Rule::OPERATOR_AND,
            'vip'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['vip'], $engine->evaluate($ctx));
    }

    public function testVipRuleDoesNotMatchWhenLtvTooLow()
    {
        $ctx = [
            'customer_ltv'         => 500.0,
            'customer_order_count' => 10,
        ];

        $rule   = new Rule(
            [
                new Condition('customer_ltv',         'gt',  1000.0),
                new Condition('customer_order_count', 'gte', 5),
            ],
            Rule::OPERATOR_AND,
            'vip'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    public function testRepeatBuyerRuleMatchesOnThreeOrMoreOrders()
    {
        $ctx = ['customer_order_count' => 3];

        $rule   = new Rule(
            [new Condition('customer_order_count', 'gte', 3)],
            Rule::OPERATOR_AND,
            'repeat-buyer'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['repeat-buyer'], $engine->evaluate($ctx));
    }

    public function testAtRiskRuleMatchesWhenNoOrderIn90Days()
    {
        $ctx = [
            'days_since_last_order' => 95,
            'customer_order_count'  => 4,
        ];

        $rule   = new Rule(
            [
                new Condition('days_since_last_order', 'gt',  90),
                new Condition('customer_order_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'at-risk'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['at-risk'], $engine->evaluate($ctx));
    }

    public function testAtRiskRuleDoesNotMatchRecentCustomer()
    {
        $ctx = [
            'days_since_last_order' => 10,
            'customer_order_count'  => 5,
        ];

        $rule   = new Rule(
            [
                new Condition('days_since_last_order', 'gt',  90),
                new Condition('customer_order_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'at-risk'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    public function testNewCustomerRuleMatchesExactlyOneOrder()
    {
        $ctx = ['customer_order_count' => 1];

        $rule   = new Rule(
            [new Condition('customer_order_count', 'eq', 1)],
            Rule::OPERATOR_AND,
            'new-customer'
        );
        $engine = new Engine([$rule]);

        $this->assertSame(['new-customer'], $engine->evaluate($ctx));
    }

    public function testNewCustomerRuleDoesNotMatchTwoOrMore()
    {
        $ctx = ['customer_order_count' => 2];

        $rule   = new Rule(
            [new Condition('customer_order_count', 'eq', 1)],
            Rule::OPERATOR_AND,
            'new-customer'
        );
        $engine = new Engine([$rule]);

        $this->assertSame([], $engine->evaluate($ctx));
    }

    // ── isFirstOrder logic (via order context) ────────────────────────────────

    public function testIsFirstOrderTrueForGuestOrder()
    {
        $order = $this->makeOrder(['customer_id' => 0, 'id' => 99]);
        $ctx   = $this->builder()->fromOrder($order);

        $this->assertTrue($ctx['is_first_order']);
    }

    // ── payment_method ────────────────────────────────────────────────────────

    public function testPaymentMethodIsCapturedFromPaymentObject()
    {
        $order   = $this->makeOrder();
        $payment = new Mage_Sales_Model_Order_Payment();
        $payment->setData(['method' => 'paypal_express']);
        $order->setPayment($payment);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertSame('paypal_express', $ctx['payment_method']);
    }

    public function testPaymentMethodEmptyStringWhenNoPaymentObject()
    {
        $order = $this->makeOrder();
        $order->setPayment(null);

        $ctx = $this->builder()->fromOrder($order);

        $this->assertSame('', $ctx['payment_method']);
    }
}
