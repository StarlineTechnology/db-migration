<?php

// Database configuration for osCommerce
$osCommerceDB = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');

// Database configuration for PrestaShop
$prestaShopDB = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');

if ($osCommerceDB->connect_error) {
    die("Connection failed: " . $osCommerceDB->connect_error);
}

if ($prestaShopDB->connect_error) {
    die("Connection failed: " . $prestaShopDB->connect_error);
}


// Fetch customers from osCommerce
$osCommerceCustomers = $osCommerceDB->query("SELECT * FROM customers");

while ($customer = $osCommerceCustomers->fetch_assoc()) {
    // Prepare data for PrestaShop
    $ps_customer_id_gender = ($customer['customers_gender'] == 'm') ? 1 : 2;
    $ps_customer_email = $customer['customers_email_address'];
    $ps_customer_password = $customer['customers_password'];
    $ps_customer_firstname = $customer['customers_firstname'];
    $ps_customer_lastname = $customer['customers_lastname'];
    $ps_customer_date_add = $customer['customers_info_date_account_created'];
    $ps_customer_date_upd = $customer['customers_info_date_account_last_modified'];

    // Insert data into PrestaShop
    $prestaShopDB->query("INSERT INTO ps_customer (
        id_gender, email, passwd, firstname, lastname, date_add, date_upd
    ) VALUES (
        '$ps_customer_id_gender', '$ps_customer_email', '$ps_customer_password',
        '$ps_customer_firstname', '$ps_customer_lastname', '$ps_customer_date_add', '$ps_customer_date_upd'
    )");

    // Get the newly inserted customer ID
    $newCustomerId = $prestaShopDB->insert_id;

    // Migrate addresses associated with this customer
    $osCommerceAddresses = $osCommerceDB->query("SELECT * FROM address_book WHERE customers_id = " . $customer['customers_id']);
    
    while ($address = $osCommerceAddresses->fetch_assoc()) {
        // Prepare address data for PrestaShop
        $ps_address_id_customer = $newCustomerId;
        $ps_address_address1 = $address['entry_street_address'];
        $ps_address_postcode = $address['entry_postcode'];
        $ps_address_city = $address['entry_city'];
        $ps_address_date_add = date("Y-m-d H:i:s");
        $ps_address_date_upd = date("Y-m-d H:i:s");

        // Insert address data into PrestaShop
        $prestaShopDB->query("INSERT INTO ps_address (
            id_customer, address1, postcode, city, date_add, date_upd
        ) VALUES (
            '$ps_address_id_customer', '$ps_address_address1', '$ps_address_postcode', '$ps_address_city',
            '$ps_address_date_add', '$ps_address_date_upd'
        )");
    }
}

// Close database connections
$osCommerceDB->close();
$prestaShopDB->close();
 

?>