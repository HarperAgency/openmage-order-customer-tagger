<?php
/**
 * HarperAgency_OrderCustomerTagger_Model_RuleEngine_ContextBuilder
 *
 * Adapts Mage_Sales_Model_Order / Mage_Customer_Model_Customer into the plain
 * context array expected by the pure-PHP RuleEngine.
 *
 * Produces the same context keys as the WooCommerce ContextBuilder so that
 * the identical Rule / Engine / Condition classes work without modification.
 */
class HarperAgency_OrderCustomerTagger_Model_RuleEngine_ContextBuilder
{
    /**
     * Build a context array from a Mage_Sales_Model_Order.
     *
     * Keys produced:
     *   order_total       float
     *   item_count        int      sum of qty across all line items
     *   shipping_method   string   shipping method code ("freeshipping_freeshipping")
     *   payment_method    string   payment method code ("checkmo", "paypal_express", etc.)
     *   payment_status    string   order status: pending, processing, complete, etc.
     *   shipping_country  string   2-letter ISO
     *   billing_country   string   2-letter ISO
     *   is_guest          bool     true when customer_id == 0
     *   is_first_order    bool     true when no prior completed/processing orders exist
     *   has_coupon        bool     true when a coupon code is set on the order
     *   address_mismatch  bool     true when billing != shipping on postcode+country
     *   items             array    [{sku, categories[]}]  — categories always [] in M1
     *
     * @param  Mage_Sales_Model_Order $order
     * @return array<string, mixed>
     */
    public function fromOrder(Mage_Sales_Model_Order $order)
    {
        // ── Line items ────────────────────────────────────────────────────────
        $items     = array();
        $itemCount = 0;

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $qty        = (int) $item->getQtyOrdered();
            $itemCount += $qty;

            $sku     = (string) $item->getSku();
            $items[] = array('sku' => $sku, 'categories' => array());
        }

        // ── Shipping method ───────────────────────────────────────────────────
        $shippingMethod = (string) $order->getShippingMethod();

        // ── Payment method ────────────────────────────────────────────────────
        $paymentMethod = '';
        $payment = $order->getPayment();
        if ($payment) {
            $paymentMethod = (string) $payment->getMethod();
        }

        // ── Boolean signals ───────────────────────────────────────────────────
        $customerId      = (int) $order->getCustomerId();
        $isGuest         = ($customerId === 0);
        $isFirstOrder    = $this->_isFirstOrder($customerId, (int) $order->getId());
        $hasCoupon       = ((string) $order->getCouponCode() !== '');
        $addressMismatch = $this->_addressesDiffer($order);

        // ── Country ───────────────────────────────────────────────────────────
        $shippingAddress = $order->getShippingAddress();
        $billingAddress  = $order->getBillingAddress();

        $shippingCountry = $shippingAddress ? (string) $shippingAddress->getCountryId() : '';
        $billingCountry  = $billingAddress  ? (string) $billingAddress->getCountryId()  : '';

        return array(
            'order_total'      => (float) $order->getGrandTotal(),
            'item_count'       => $itemCount,
            'shipping_method'  => $shippingMethod,
            'payment_method'   => $paymentMethod,
            'payment_status'   => (string) $order->getStatus(),
            'shipping_country' => $shippingCountry,
            'billing_country'  => $billingCountry,
            'is_guest'         => $isGuest,
            'is_first_order'   => $isFirstOrder,
            'has_coupon'       => $hasCoupon,
            'address_mismatch' => $addressMismatch,
            'items'            => $items,
        );
    }

    /**
     * Build a context array from a customer ID.
     *
     * Keys produced:
     *   customer_ltv               float   sum of all completed order totals
     *   customer_order_count       int     number of completed / processing orders
     *   customer_account_age_days  int     days since account created_at
     *   days_since_last_order      int     days since most recent order (0 if none)
     *   customer_tags              string[]  slugs of tags applied to this customer
     *
     * @param  int $customerId
     * @return array<string, mixed>
     */
    public function fromCustomerId($customerId)
    {
        $customerId = (int) $customerId;

        // ── Order stats ───────────────────────────────────────────────────────
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addFieldToFilter('customer_id', $customerId);
        $orderCollection->addFieldToFilter('status', array(
            'in' => array('complete', 'processing'),
        ));

        $ltv        = 0.0;
        $orderCount = 0;
        $lastOrderTimestamp = 0;

        foreach ($orderCollection as $o) {
            $ltv        += (float) $o->getGrandTotal();
            $orderCount++;
            $createdAt = strtotime((string) $o->getCreatedAt());
            if ($createdAt > $lastOrderTimestamp) {
                $lastOrderTimestamp = $createdAt;
            }
        }

        $daysSinceLastOrder = 0;
        if ($lastOrderTimestamp > 0) {
            $daysSinceLastOrder = (int) floor((time() - $lastOrderTimestamp) / 86400);
        }

        // ── Account age ───────────────────────────────────────────────────────
        $accountAgeDays = 0;
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if ($customer->getId()) {
            $createdAt = strtotime((string) $customer->getCreatedAt());
            if ($createdAt) {
                $accountAgeDays = (int) floor((time() - $createdAt) / 86400);
            }
        }

        // ── Existing customer tags ────────────────────────────────────────────
        $existingTags = array();
        $ctCollection = Mage::getModel('harper_tagger/customer_tag')->getCollection();
        $ctCollection->addFieldToFilter('customer_id', $customerId);
        foreach ($ctCollection as $ct) {
            $tagId = (int) $ct->getTagId();
            $tag   = Mage::getModel('harper_tagger/tag')->load($tagId);
            if ($tag->getId()) {
                $existingTags[] = (string) $tag->getSlug();
            }
        }

        return array(
            'customer_ltv'              => $ltv,
            'customer_order_count'      => $orderCount,
            'customer_account_age_days' => $accountAgeDays,
            'days_since_last_order'     => $daysSinceLastOrder,
            'customer_tags'             => $existingTags,
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * True when this is the customer's first completed/processing order.
     * Guests always return true (no history to check).
     *
     * @param  int $customerId
     * @param  int $currentOrderId
     * @return bool
     */
    protected function _isFirstOrder($customerId, $currentOrderId)
    {
        if ($customerId === 0) {
            return true;
        }

        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('status', array(
            'in' => array('complete', 'processing'),
        ));
        $collection->addFieldToFilter('entity_id', array('neq' => $currentOrderId));
        $collection->setPageSize(1);

        return ($collection->getSize() === 0);
    }

    /**
     * True when billing and shipping differ on postcode or country.
     *
     * @param  Mage_Sales_Model_Order $order
     * @return bool
     */
    protected function _addressesDiffer(Mage_Sales_Model_Order $order)
    {
        $billing  = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();

        if (!$billing || !$shipping) {
            return false;
        }

        return
            (string) $billing->getCountryId() !== (string) $shipping->getCountryId() ||
            (string) $billing->getPostcode()  !== (string) $shipping->getPostcode();
    }
}
