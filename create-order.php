<?php

require_once 'app/Mage.php';
umask(0);
Mage::app();

$email              = "test@test.com";
$firstName          = "Test";
$lastName           = "Test";
$password           = "123456";
$productID          = 1;
$productQty         = 2;
$creditCardcID      = 123;
$creditCardType     = 'VI';  // visa = VI, American Express= AE, MasterCard = MC, Discover = DI
$creditNo           = '4111111111111111';


$websiteId = Mage::app()->getWebsite()->getId();
$store = Mage::app()->getStore();

//check customer already exists Mage::app()->getWebsite('admin')->getId()
$customer = Mage::getModel("customer/customer");
$customer->setWebsiteId($websiteId);
$customer->loadByEmail($email);
if($customer->getId()) {
    try{
        $customerId = $customer->getId();
    }catch (Exception $e){
        Zend_Debug::dump($e->getMessage());
    }
}else {
    $customer = Mage::getModel("customer/customer");
    $customer->setWebsiteId($websiteId)
        ->setStore($store)
        ->setFirstname($firstName)
        ->setLastname($lastName)
        ->setEmail($email)
        ->setPassword($password);

    try{
        $customer->save();
        echo 'Customer is created successfully.';
    }
    catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
    }

    // set default address
    $address = Mage::getModel("customer/address");
    $address->setCustomerId($customer->getId())
        ->setFirstname($customer->getFirstname())
        ->setMiddleName($customer->getMiddlename())
        ->setLastname($customer->getLastname())
        ->setCountryId('UK')
        ->setPostcode('12345')
        ->setCity('New york')
        ->setTelephone('123456789')
        ->setFax('123456789')
        ->setCompany('Testing')
        ->setStreet('first street')
        ->setIsDefaultBilling('1')
        ->setIsDefaultShipping('1')
        ->setSaveInAddressBook('1');

    $customerId = $customer->getId();
    try{
        $address->save();
    }
    catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
    }
}

$customer = Mage::getModel("customer/customer")->load($customerId);
$transaction = Mage::getModel('core/resource_transaction');
$storeId = $customer->getStoreId();
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

// set Billing Address
$billing = $customer->getDefaultBillingAddress();
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

$shipping = $customer->getDefaultShippingAddress();
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
    ->setShippingDescription('Flat Rate - Fixed')
    ->setShipping_method('flatrate_flatrate');

/*->setShippingDescription($this->getCarrierName('flatrate'));*/
/*some error i am getting here need to solve further*/

//you can set your payment method name here as per your need
$orderPayment = Mage::getModel('sales/order_payment')
    ->setStoreId($storeId)
    ->setCcExpMonth(12)
//    ->setMethod('purchaseorder')
    ->setMethod('ccsave')
    ->setCcLast4(substr($creditNo,-4))
    ->setCcOwner($customer->getFirstname())
    ->setCcType($creditCardType)
    ->setCcCid($creditCardcID)
    ->setCcExpYear(date("Y"))
    ->setCcNumberEnc(Mage::helper('core')->encrypt($creditNo))
;

$order->setPayment($orderPayment);

$subTotal = 0;
$products = array($productID => array( 'qty' => $productQty ));

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
