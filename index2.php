<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings for osCommerce and PrestaShop
$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop');


// Query to get customers and addresses data
$query = "SELECT c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_email_address, 
                 cp.customers_password, cm.customers_md5, 
                 ab.entry_firstname, ab.entry_lastname, ab.entry_street_address, 
                 ab.entry_postcode, ab.entry_city, ab.entry_country_id
          FROM customers c
          LEFT JOIN customers_password cp ON c.customers_id = cp.customers_id
          LEFT JOIN customers_md5 cm ON c.customers_id = cm.customers_id
          LEFT JOIN address_book ab ON c.customers_id = ab.customers_id";

$result = $oscommerce_db->query($query);

while ($row = $result->fetch_assoc()) {
    $customer_id = $row['customers_id'];
    $firstname = $row['customers_firstname']);
    $lastname = $row['customers_lastname']);
    $email = $row['customers_email_address']);
    $password = $row['customers_password']);
    $date_add = $row['customers_info_date_account_created']);
    $customer_gender = $row['customers_gender'];
    $id_gender = ($customer_gender == 'm' || $customer_gender == 'f') ? 1 : 2;
    $date_upd = date('Y-m-d H:i:s');

    $company = $row['entry_company']);
    $siret = $row['entry_company_tax_id']);
    $active = $row['customers_login_allowed']);
    $taxid = $row['entry_company_tax_id']);
    $street = $row['entry_street_address']);
    $post = $row['entry_postcode']);
    $city = $row['entry_city']);
    $country = $row['entry_country_id']);
    $telephone = $row['customers_telephone']);
    $fax = $row['customers_fax']);
    
    // Rehash the password
    $new_password_hash = password_hash($old_password_hash, PASSWORD_BCRYPT);
    
    // Insert customer data into PrestaShop
    $insertCustomerQuery = "INSERT INTO ps_customer (id_customer, id_gender, firstname, lastname, email, passwd, date_add, date_upd, active, company, siret, company_name, company_taxid, street_address, post_code, city_name, country_name, telephone_number, fax_number)
              VALUES ($id_customer, $id_gender, '$firstname', '$lastname', '$email', '$new_password_hash', '$date_add', '$date_upd', '$active', '$company', '$siret', '$company', '$taxid', '$street', '$post', '$city', '$country', '$telephone', '$fax')";
    $prestashop_db->query($insertCustomerQuery);
    
    // Prepare address data
    $address_firstname = $row['entry_firstname'];
    $address_lastname = $row['entry_lastname'];
    $street_address = $row['entry_street_address'];
    $postcode = $row['entry_postcode'];
    $city = $row['entry_city'];
    $country_id = $row['entry_country_id'];
    
    // Insert address data into PrestaShop
    $insertAddressQuery = "INSERT INTO ps_address (id_customer, firstname, lastname, address1, postcode, city, id_country, date_add, date_upd, active)
                           VALUES ('$customer_id', '$address_firstname', '$address_lastname', '$street_address', '$postcode', '$city', '$country_id', NOW(), NOW(), 1)";
    $prestashop_db->query($insertAddressQuery);
}

$oscommerce_db->close();
$prestashop_db->close();
?>

