<?php
include 'app/Mage.php';
Mage::app();

const SEPARATOR = '/';

// collection of product
$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
$collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('name')
                ->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);

if($collection){
    foreach($collection->getData() as $products) {
        $newPath = '';

        if($products['entity_id'] == 167){

            // product name
            $name = strtolower($products['name']);
			$name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
			$name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);

            // Media url
            $path = 'media/catalog/product';

            // Create directory
            if($name){
                $fileName = explode('.', $name);
                $newImageName = $fileName[0];
                $firstDir = substr($newImageName, 0, 1);
                $secondDir = substr($newImageName, 1, 1);
                if(!is_dir($path.SEPARATOR.$firstDir)){
                    mkdir($path.SEPARATOR.$firstDir, 0700);
                }
                if(!is_dir($path.SEPARATOR.$firstDir.SEPARATOR.$secondDir)){
                    mkdir($path.SEPARATOR.$firstDir.SEPARATOR.$secondDir, 0700);
                }
                $newPath = $path.SEPARATOR.$firstDir.SEPARATOR.$secondDir;
            }

            $images = array();
            $product = Mage::getModel('catalog/product')->load($products['entity_id']);
            // set label of all image

//            $product->setImageLabel($products['name']);
//            $product->setSmallImageLabel($products['name']);
//            $product->setThumbnailLabel($products['name']);

            $baseImage = $product->getImage();

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $writeConnection = $resource->getConnection('core_write');

            $galleryTable = $resource->getTableName('catalog/product_attribute_media_gallery');
            $galleryValueTable = $resource->getTableName('catalog/product_attribute_media_gallery_value');
            $catalogVarchar = $resource->getTableName('catalog_product_entity_varchar');

            //SELECT `value_id` FROM `magento1910`.`catalog_product_entity_varchar` WHERE (`value` LIKE '%retro-chic-eyeglasses-0.jpg%')
            $i=1;foreach ($product->getMediaGallery('images') as $key=>$image) {
                $extension = end(explode(".",$image['file']));
                $oldValue = "'".$image['file']."'";
                $query = 'SELECT value_id FROM ' . $catalogVarchar .  ' WHERE value = '.$oldValue;
                //echo $query;exit;
                $varcharValueIds = $readConnection->fetchAll($query);
                var_dump($varcharValueIds);
                /*// update image name
                $oldImagePath = trim($path.$image['file']);
                $newImagePath = $newPath.SEPARATOR.$name.'-'.$i.'.'.$extension;
                $valueId = $image['value_id'];
                $value = SEPARATOR.$firstDir.SEPARATOR.$secondDir.SEPARATOR.$name.'-'.$i.'.'.$extension;
                if(!file_exists($newImagePath)){rename($oldImagePath, $newImagePath);}

                $query = "UPDATE {$galleryTable} SET value = '{$value}' WHERE value_id = ". $valueId;
                $writeConnection->query($query);

                foreach($varcharValueIds as $varcharValueId){
                    $imageValueId = $varcharValueId['value_id'];
                    $query = "UPDATE {$catalogVarchar} SET value = '{$value}' WHERE value_id = ". $imageValueId;
                    $writeConnection->query($query);
                }*/
                $i++;
            }
        }
    }
}
