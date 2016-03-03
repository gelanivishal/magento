<?php
include 'app/Mage.php';
Mage::app();

const SEPARATOR = '/';

// collection of product
$storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
$collection = Mage::getModel('catalog/product')
    ->getCollection();
$notAssignedImages = array();
$force_assign_images = 0;
parse_str($_SERVER['QUERY_STRING']);

$resource = Mage::getSingleton('core/resource');
$readConnection = $resource->getConnection('core_read');
$writeConnection = $resource->getConnection('core_write');

if($collection){
    foreach($collection->getData() as $products) {
        $newPath = '';
        $product = Mage::getModel('catalog/product')->load($products['entity_id']);
        $productMediaGallery = $product->getMediaGallery('images');
        $productId = $products['entity_id'];
        if(count($productMediaGallery)>0){
        }
        else {
            $notAssignedImages[] = $products['sku'];
        }
        reset($isNoSelections);
    }
}
//echo 'Base Image, Small Image and Thumbnail are not assigned products:';echo '<br>';
if($notAssignedImages){
//    echo count($notAssignedImages);
    echo implode('<br />', $notAssignedImages);
}else {
    print 'No product found.';
}
