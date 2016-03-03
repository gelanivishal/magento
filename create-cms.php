<?php

include_once 'app/Mage.php';
umask(0);
Mage::app("default");

error_reporting(E_ALL);

//Create Static Block
$cmsPage = array(
                'title' => 'My CMS page',
                'identifier' => 'my-cms-page',
                'content' => 'Lorem ipsum dolor sit, amen hte gulocse',
                'is_active' => 1,
                'sort_order' => 0,
                'root_template' => 'three_columns'
                'stores' => array(Mage_Core_Model_App::ADMIN_STORE_ID)
                );

Mage::getModel('cms/page')->setData($cmsPage)->save();
