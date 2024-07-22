<?php
//$oscommerce_db = new mysqli('127.0.0.1:3306', 'admin', 'admin', 'test_lamali_oscommerce');
//$prestashop_db = new mysqli('127.0.0.1:3306', 'admin', 'admin', 'test_lamali_prestashop');


$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop'); 


// Check connections
if ($oscommerce_db->connect_error) die("<span style='color:red'>osCommerce connection failed: <b>" . $oscommerce_db->connect_error."</b></span>");
if ($prestashop_db->connect_error) die("<span style='color:red'>PrestaShop connection failed: <b>". $prestashop_db->connect_error."</b></span>");

echo "<style>span {display: inline-block;width: 100%;}</style>";
echo '<a href="./" style="text-align:center;display: block;">Home</a>';
echo "<span style='color:green'>Connected to both databases successfully.</span><br>";

function verifyDate($date, $strict = true, $format = 'Y-m-d H:i:s')
{
    $dateTime = DateTime::createFromFormat($format, $date);
    if ($strict) {
        $errors = DateTime::getLastErrors();
        if (!empty($errors['warning_count'])) {
            return false;
        }
    }
    return $dateTime !== false;
}