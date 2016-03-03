<?php
require_once 'app/Mage.php';
Mage::app();

$orderId = 382;

Mage::unregister('rule_data');
Mage::getSingleton('adminhtml/session_quote')->clear();

$order = Mage::getModel('sales/order')->load($orderId);
$incId = $order->getIncrementId();

$newQuote = new Mage_Sales_Model_Quote();
$newQuote->setStoreId($order->getStoreId());
Mage::getSingleton('adminhtml/sales_order_create')->setQuote($newQuote);

$order_model = Mage::getSingleton('adminhtml/sales_order_create');
$order_model->getSession()->clear();

try {
    $order->setReordered(true);
    Mage::getSingleton('adminhtml/session_quote')->setUseOldShippingMethod(true);

    $reorder = new Varien_Object();
    $reorder = $order_model->initFromOrder($order);
    $newOrder = $reorder->createOrder();

    $reOrderId = $newOrder->getId();
    $reOrderIncId = $newOrder->getIncrementId();
    Mage::log("Order #{$incId} is Reorders To New Order #{$reOrderIncId} Successfully",null,"reorder.log");
} catch (Exception $e) {
    Mage::log("Order #{$incId} Reorder Error : {$e->getMessage()}",null,"reorder.log");
}
$reorder->getSession()->clear();
Mage::unregister('rule_data');
Mage::getSingleton('adminhtml/session_quote')->clear();

//---------------------------------------------------------------

//run the reminders

require_once 'app/Mage.php';
Mage::app();
$websiteId = Mage::app()->getWebsite()->getId();
$store = Mage::app()->getStore();
$storeId = Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();

$incrementOrderId = 100000300;
$creditCardCID = 000;
$_order = Mage::getModel('sales/order')->load($incrementOrderId, 'increment_id');

$customer = Mage::getModel("customer/customer");
$customer->setWebsiteId($websiteId);
$customer->loadByEmail($order['customer_email']);
//$customer = Mage::getModel("customer/customer")->load($customer->getId());

$transaction = Mage::getModel('core/resource_transaction');
$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);

$order = Mage::getModel('sales/order')
    ->setIncrementId($reservedOrderId)
    ->setStoreId($storeId)
    ->setGlobal_currency_code('USD')
    ->setBase_currency_code('USD')
    ->setStore_currency_code('USD')
    ->setOrder_currency_code('USD')
    ->setQuoteId(0);

// set Customer data
$order->setCustomer_email($customer->getEmail())
    ->setCustomerFirstname($customer->getFirstname())
    ->setCustomerLastname($customer->getLastname())
    ->setCustomerGroupId($customer->getGroupId())
    ->setCustomer_is_guest(0)
    ->setCustomer($customer);

$billing = Mage::getModel('sales/order_address')->load($_order->getBillingAddressId());
$billingAddress = Mage::getModel('sales/order_address')
    ->setStoreId($storeId)
    ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
    ->setCustomerId($customer->getId())
    ->setCustomerAddressId($customer->getDefaultBilling())
    ->setCustomer_address_id($billing->getEntityId())
    ->setPrefix($billing->getPrefix())
    ->setFirstname($billing->getFirstname())
    ->setMiddlename($billing->getMiddlename())
    ->setLastname($billing->getLastname())
    ->setSuffix($billing->getSuffix())
    ->setCompany($billing->getCompany())
    ->setStreet($billing->getStreet())
    ->setCity($billing->getCity())
    ->setCountry_id($billing->getCountryId())
    ->setRegion($billing->getRegion())
    ->setRegion_id($billing->getRegionId())
    ->setPostcode($billing->getPostcode())
    ->setTelephone($billing->getTelephone())
    ->setFax($billing->getFax());
$order->setBillingAddress($billingAddress);

$shipping = Mage::getModel('sales/order_address')->load($_order->getShippingAddressId());
$shippingAddress = Mage::getModel('sales/order_address')
    ->setStoreId($storeId)
    ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
    ->setCustomerId($customer->getId())
    ->setCustomerAddressId($customer->getDefaultShipping())
    ->setCustomer_address_id($shipping->getEntityId())
    ->setPrefix($shipping->getPrefix())
    ->setFirstname($shipping->getFirstname())
    ->setMiddlename($shipping->getMiddlename())
    ->setLastname($shipping->getLastname())
    ->setSuffix($shipping->getSuffix())
    ->setCompany($shipping->getCompany())
    ->setStreet($shipping->getStreet())
    ->setCity($shipping->getCity())
    ->setCountry_id($shipping->getCountryId())
    ->setRegion($shipping->getRegion())
    ->setRegion_id($shipping->getRegionId())
    ->setPostcode($shipping->getPostcode())
    ->setTelephone($shipping->getTelephone())
    ->setFax($shipping->getFax());
$order->setShippingAddress($shippingAddress)
    ->setShippingDescription($_order->getShippingDescription())
    ->setShipping_method($_order->getShippingMethod());

$payment = Mage::getModel('sales/order_payment')->load($_order->getId());

$orderPayment = Mage::getModel('sales/order_payment')
    ->setStoreId($storeId)
    ->setMethod($payment->getMethod());
if($payment->getCcNumberEnc()){
    $creditCardNo = $payment->getCcNumberEnc();
}elseif($payment->getCcType() == 'VI' && !$payment->getCcNumberEnc()){
    $creditCardNo = Mage::helper('core')->encrypt('4111111111111111');
}elseif ($payment->getCcType() == 'MC' && !$payment->getCcNumberEnc()){
    $creditCardNo = Mage::helper('core')->encrypt('5555555555554444');
}elseif ($payment->getCcType() == 'AE' && !$payment->getCcNumberEnc()){
    $creditCardNo = Mage::helper('core')->encrypt('378282246310005');
}

if($payment->getMethod() == 'ccsave'){
    $orderPayment->setCcExpMonth(12)
        ->setCcLast4($payment->getCcLast4())
        ->setCcOwner($payment->getCcOwner())
        ->setCcType($payment->getCcType())
        ->setCcCid($creditCardCID)
        ->setCcExpYear(date("Y"))
        ->setCcNumberEnc($creditCardNo)
    ;
}
$order->setPayment($orderPayment);

$subTotal = 0;
$quoteItems = Mage::getModel('sales/order_item')
    ->getCollection()
    ->addFieldToFilter('order_id', array( 'eq' => array($_order->getId())));

foreach($quoteItems as $quoteItem){
    if(!$quoteItem->getProductOptions()){
        $products[$quoteItem->getProductId()] = array( 'qty' => 1);
    }else{
        $options = $quoteItem->getProductOptions();
        $products[$options[info_buyRequest][product]] = array( 'qty' => $options[info_buyRequest][qty]);
    }
}

foreach ($products as $productId=>$product) {
    $_product = Mage::getModel('catalog/product')->load($productId);
    $rowTotal = $_product->getPrice() * $product['qty'];
    $orderItem = Mage::getModel('sales/order_item')
        ->setStoreId($storeId)
        ->setQuoteItemId(0)
        ->setQuoteParentItemId(NULL)
        ->setProductId($productId)
        ->setProductType($_product->getTypeId())
        ->setQtyBackordered(NULL)
        ->setTotalQtyOrdered($product['rqty'])
        ->setQtyOrdered($product['qty'])
        ->setName($_product->getName())
        ->setSku($_product->getSku())
        ->setPrice($_product->getPrice())
        ->setBasePrice($_product->getPrice())
        ->setOriginalPrice($_product->getPrice())
        ->setRowTotal($rowTotal)
        ->setBaseRowTotal($rowTotal);

    $subTotal += $rowTotal;
    $order->addItem($orderItem);
}

$order->setSubtotal($subTotal)
    ->setBaseSubtotal($subTotal)
    ->setGrandTotal($subTotal)
    ->setBaseGrandTotal($subTotal);

$transaction->addObject($order);
$transaction->addCommitCallback(array($order, 'place'));
$transaction->addCommitCallback(array($order, 'save'));
$transaction->save();
