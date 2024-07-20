<?php
//$oscommerce_db = new mysqli('127.0.0.1:3306', 'admin', 'admin', 'test_lamali_oscommerce');
//$prestashop_db = new mysqli('127.0.0.1:3306', 'admin', 'admin', 'test_lamali_prestashop');


$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop'); 


// Check connections
if ($oscommerce_db->connect_error) die("osCommerce connection failed: " . $oscommerce_db->connect_error);
if ($prestashop_db->connect_error) die("PrestaShop connection failed: " . $prestashop_db->connect_error);

echo "Connected to both databases successfully.<br>";