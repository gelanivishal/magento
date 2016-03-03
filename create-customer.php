<?php

require_once 'app/Mage.php';
umask(0);
Mage::app();

$firstName = "Test";
$lastName = "Test";
$password = "123456";
$email = "test@test.com";
$websiteId = Mage::app()->getWebsite()->getId();
$store = Mage::app()->getStore();

//check customer already exists Mage::app()->getWebsite('admin')->getId()
$customer = Mage::getModel("customer/customer");
$customer->setWebsiteId($websiteId);
$customer->loadByEmail($email);
if($customer->getId()) {
    $id = $customer->getId();
    echo "customer already exists. Customer id : ". $id;
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

    $address = Mage::getModel("customer/address");
    $address->setCustomerId($customer->getId())
        ->setFirstname($customer->getFirstname())
        ->setMiddleName($customer->getMiddlename())
        ->setLastname($customer->getLastname())
        ->setCountryId('US')
        //->setRegionId('1') //state/province, only needed if the country is USA
        ->setPostcode('12345')
        ->setCity('New york')
        ->setTelephone('123456789')
        ->setFax('123456789')
        ->setCompany('Testing')
        ->setStreet('first street')
        ->setIsDefaultBilling('1')
        ->setIsDefaultShipping('1')
        ->setSaveInAddressBook('1');

    try{
        $address->save();
    }
    catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
    }
}
