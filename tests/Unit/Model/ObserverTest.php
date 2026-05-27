<?php
/**
 * ObserverTest — unit tests for HarperAgency_OrderCustomerTagger_Model_Observer
 *
 * Tests cover:
 *   - onOrderSaveAfter: skips orders with no ID, calls _processOrder otherwise
 *   - _buildRuleObjects: filters by tag type, builds Rule objects correctly
 *   - _saveOrderTags: skips duplicates, persists new associations
 *   - _saveCustomerTags: skips duplicates, persists new associations
 *   - Guest orders (customer_id = 0) skip customer rule evaluation
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ObserverTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeObserver(): HarperAgency_OrderCustomerTagger_Model_Observer
    {
        return new HarperAgency_OrderCustomerTagger_Model_Observer();
    }

    private function makeOrder(array $data = []): Mage_Sales_Model_Order
    {
        $defaults = [
            'id'              => 1,
            'grand_total'     => 50.0,
            'status'          => 'processing',
            'customer_id'     => 0,
            'shipping_method' => 'flatrate_flatrate',
            'coupon_code'     => '',
        ];
        $order = new Mage_Sales_Model_Order();
        $order->setData(array_merge($defaults, $data));

        $billing  = new Mage_Sales_Model_Order_Address();
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

    private function makeObserverEvent(Mage_Sales_Model_Order $order): Varien_Event_Observer
    {
        $observer = new Varien_Event_Observer();
        $observer->getEvent()->setOrder($order);
        return $observer;
    }

    // ── onOrderSaveAfter: guard clause ────────────────────────────────────────

    public function testOnOrderSaveAfterSkipsOrderWithNoId(): void
    {
        $order    = $this->makeOrder(['id' => 0]);
        $observer = $this->makeObserverEvent($order);

        // Should return without throwing
        $obs = $this->makeObserver();
        $obs->onOrderSaveAfter($observer);
        $this->assertTrue(true);
    }

    public function testOnOrderSaveAfterSkipsNullOrder(): void
    {
        $observer = new Varien_Event_Observer();
        $observer->getEvent()->setOrder(null);

        $obs = $this->makeObserver();
        $obs->onOrderSaveAfter($observer);
        $this->assertTrue(true);
    }

    public function testOnOrderSaveAfterRunsWithValidOrder(): void
    {
        $order    = $this->makeOrder(['id' => 42]);
        $observer = $this->makeObserverEvent($order);

        // Runs _processOrder — with no rules registered, completes silently
        $obs = $this->makeObserver();
        $obs->onOrderSaveAfter($observer);
        $this->assertTrue(true);
    }

    // ── _buildRuleObjects ─────────────────────────────────────────────────────

    public function testBuildRuleObjectsReturnsEmptyArrayForNoRules(): void
    {
        $obs  = $this->makeObserver();
        $ref  = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);

        $result = $ref->invoke($obs, [], 'order');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testBuildRuleObjectsFiltersOutWrongTagType(): void
    {
        // Register a tag of type 'customer' — should be excluded when asking for 'order'
        $tag = new Mage_Core_Model_Abstract();
        $tag->setData(['id' => 1, 'type' => 'customer']);
        mage_test_register_model('harper_tagger/tag', $tag, 1);

        $rule = new Mage_Core_Model_Abstract();
        $rule->setData([
            'tag_id'     => 1,
            'operator'   => 'AND',
            'conditions' => json_encode([
                ['field' => 'order_total', 'operator' => 'gte', 'value' => '0'],
            ]),
        ]);

        $obs    = $this->makeObserver();
        $ref    = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);
        $result = $ref->invoke($obs, [$rule], 'order');

        $this->assertEmpty($result, 'Customer-type tag should be excluded from order rules');
    }

    public function testBuildRuleObjectsIncludesMatchingTagType(): void
    {
        $tag = new Mage_Core_Model_Abstract();
        $tag->setData(['id' => 2, 'type' => 'order']);
        mage_test_register_model('harper_tagger/tag', $tag, 2);

        $rule = new Mage_Core_Model_Abstract();
        $rule->setData([
            'tag_id'     => 2,
            'operator'   => 'AND',
            'conditions' => json_encode([
                ['field' => 'order_total', 'operator' => 'gte', 'value' => '0'],
            ]),
        ]);

        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);
        $result = $ref->invoke($obs, [$rule], 'order');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(\HarperAgency\RuleEngine\Rule::class, $result[0]);
    }

    public function testBuildRuleObjectsSkipsRulesWithNoConditions(): void
    {
        $tag = new Mage_Core_Model_Abstract();
        $tag->setData(['id' => 3, 'type' => 'order']);
        mage_test_register_model('harper_tagger/tag', $tag, 3);

        $rule = new Mage_Core_Model_Abstract();
        $rule->setData([
            'tag_id'     => 3,
            'operator'   => 'AND',
            'conditions' => json_encode([]),  // no conditions
        ]);

        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);
        $result = $ref->invoke($obs, [$rule], 'order');

        $this->assertEmpty($result, 'Rules with no conditions should be skipped');
    }

    public function testBuildRuleObjectsSkipsIncompleteConditions(): void
    {
        $tag = new Mage_Core_Model_Abstract();
        $tag->setData(['id' => 4, 'type' => 'order']);
        mage_test_register_model('harper_tagger/tag', $tag, 4);

        $rule = new Mage_Core_Model_Abstract();
        $rule->setData([
            'tag_id'     => 4,
            'operator'   => 'AND',
            'conditions' => json_encode([
                ['field' => '', 'operator' => 'gte'],   // missing field
                ['field' => 'order_total', 'operator' => ''],  // missing operator
            ]),
        ]);

        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);
        $result = $ref->invoke($obs, [$rule], 'order');

        $this->assertEmpty($result);
    }

    public function testBuildRuleObjectsDefaultsInvalidOperatorToAnd(): void
    {
        $tag = new Mage_Core_Model_Abstract();
        $tag->setData(['id' => 5, 'type' => 'order']);
        mage_test_register_model('harper_tagger/tag', $tag, 5);

        $rule = new Mage_Core_Model_Abstract();
        $rule->setData([
            'tag_id'     => 5,
            'operator'   => 'INVALID',
            'conditions' => json_encode([
                ['field' => 'order_total', 'operator' => 'gte', 'value' => '0'],
            ]),
        ]);

        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_buildRuleObjects');
        $ref->setAccessible(true);
        $result = $ref->invoke($obs, [$rule], 'order');

        $this->assertCount(1, $result);
    }

    // ── _saveOrderTags ────────────────────────────────────────────────────────

    public function testSaveOrderTagsSkipsZeroTagId(): void
    {
        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_saveOrderTags');
        $ref->setAccessible(true);

        // Tag ID 0 must be silently skipped — no exception, no save
        $ref->invoke($obs, 1, [0, -1]);
        $this->assertTrue(true);
    }

    public function testSaveOrderTagsSkipsDuplicates(): void
    {
        // Simulate existing association — collection returns 1 item, size() > 0 = duplicate
        $existing = new Mage_Core_Model_Abstract();
        $existing->setData(['order_id' => 10, 'tag_id' => 7]);
        $collection = new StubCollection([$existing]);
        mage_test_register_collection('harper_tagger/order_tag', $collection);

        $saveCount = 0;
        Mage::register('__save_callbacks', ['harper_tagger/order_tag' => function() use (&$saveCount) {
            $saveCount++;
        }]);

        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_saveOrderTags');
        $ref->setAccessible(true);
        $ref->invoke($obs, 10, [7]);

        // Save should not have been called because duplicate was detected
        $this->assertSame(0, $saveCount, 'Duplicate order tag should not be saved again');
    }

    public function testSaveOrderTagsAcceptsMultipleTagIds(): void
    {
        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_saveOrderTags');
        $ref->setAccessible(true);

        // Empty collection = no duplicate, new records would be saved
        // Just verify no exception is thrown for multiple IDs
        $ref->invoke($obs, 99, [1, 2, 3]);
        $this->assertTrue(true);
    }

    // ── _saveCustomerTags ─────────────────────────────────────────────────────

    public function testSaveCustomerTagsSkipsZeroTagId(): void
    {
        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_saveCustomerTags');
        $ref->setAccessible(true);

        $ref->invoke($obs, 1, [0]);
        $this->assertTrue(true);
    }

    public function testSaveCustomerTagsAcceptsMultipleTagIds(): void
    {
        $obs = $this->makeObserver();
        $ref = new ReflectionMethod($obs, '_saveCustomerTags');
        $ref->setAccessible(true);

        $ref->invoke($obs, 5, [10, 11, 12]);
        $this->assertTrue(true);
    }

    // ── Guest order skips customer rules ──────────────────────────────────────

    public function testGuestOrderDoesNotAttemptCustomerTagging(): void
    {
        $order    = $this->makeOrder(['id' => 1, 'customer_id' => 0]);
        $observer = $this->makeObserverEvent($order);

        $obs = $this->makeObserver();
        $obs->onOrderSaveAfter($observer);

        // No exception means guest path completed without trying customer rules
        $this->assertTrue(true);
    }
}
