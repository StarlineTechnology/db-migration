<?php
$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop');


// Check connections
if ($oscommerce_db->connect_error) die("osCommerce connection failed: " . $oscommerce_db->connect_error);
if ($prestashop_db->connect_error) die("PrestaShop connection failed: " . $prestashop_db->connect_error);

echo "Connected to both databases successfully.<br>";

//
// Table structure for table `zc_legacy_passwords`
//

$sql = "CREATE TABLE IF NOT EXISTS `zc_legacy_passwords` (
  `id` int UNSIGNED NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updated` tinyint(1) NOT NULL
)";
$prestashop_db->query($sql);



// Function to escape and format values for SQL
function escape($db, $value) {
    return $db->real_escape_string($value);
}

// // PrestaShop password hash function
// function hashPassword($password) {
//     return password_hash($password, PASSWORD_BCRYPT);
// }

// // Default password for migrated users
// $default_password = "default_password";
// $hashed_default_password = hashPassword($default_password);

// Migrate customers
$customers = $oscommerce_db->query("
                                    SELECT c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_email_address, c.customers_login_allowed, c.customers_password, c.customers_telephone, c.customers_fax, 
                                    a.entry_gender, a.entry_company, a.entry_company_tax_id, a.entry_street_address, a.entry_postcode, a.entry_city, a.entry_country_id, ci.customers_info_date_account_created
                                    FROM customers c 
                                    JOIN address_book a ON c.customers_id = a.customers_id
                                    LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id");

if ($customers === false) {
    die("Error fetching customers: " . $oscommerce_db->error);
}

echo "Migrating customers...<br><br><br>";

// Gender mapping function
function mapGender($gender) {
    if ($gender == 'm' || $gender == 'M') {
        return 1; // Male
    } elseif ($gender == 'f' || $gender == 'F') {
        return 2; // Female
    } else {
        return 0; // Unknown
    }
}

while ($customer = $customers->fetch_assoc()) {
    $id_customer = $customer['customers_id'];
    $firstname = escape($prestashop_db, $customer['customers_firstname']);
    $lastname = escape($prestashop_db, $customer['customers_lastname']);
    $email = escape($prestashop_db, $customer['customers_email_address']);
    $password = escape($prestashop_db, $customer['customers_password']);
    $passwordmd5 = escape($prestashop_db, $customer['customers_md5']);
    $date_add = escape($prestashop_db, $customer['customers_info_date_account_created']);
    $customer_gender = escape($prestashop_db, $customer['customers_gender']);
    $id_gender = mapGender($customer_gender);
    $date_upd = date('Y-m-d H:i:s');

    $company = escape($prestashop_db, $customer['entry_company']);
    $siret = escape($prestashop_db, $customer['entry_company_tax_id']);
    $active = escape($prestashop_db, $customer['customers_login_allowed']);
    $taxid = escape($prestashop_db, $customer['entry_company_tax_id']);
    $street = escape($prestashop_db, $customer['entry_street_address']);
    $post = escape($prestashop_db, $customer['entry_postcode']);
    $city = escape($prestashop_db, $customer['entry_city']);
    $country = escape($prestashop_db, $customer['entry_country_id']);
    $telephone = escape($prestashop_db, $customer['customers_telephone']);
    $fax = escape($prestashop_db, $customer['customers_fax']);

    $query = "INSERT INTO ps_customer (id_customer, id_gender, firstname, lastname, email, passwd, date_add, date_upd, active, company, siret, company_name, company_taxid, street_address, post_code, city_name, country_name, telephone_number, fax_number)
              VALUES ($id_customer, $id_gender, '$firstname', '$lastname', '$email', '$password', '$date_add', '$date_upd', '$active', '$company', '$siret', '$company', '$taxid', '$street', '$post', '$city', '$country', '$telephone', '$fax')";
              
    if (!$prestashop_db->query($query)) {
        echo "Error inserting customer ID $id_customer: " . $prestashop_db->error . "<br><br><br><br>";
    } else {
        echo "Customer ID $id_customer migrated successfully.<br>";
    }
    $customer = $prestashop_db->query("SELECT * FROM `zc_legacy_passwords` WHERE `email` = '$email'");
    if ($customer === false) {
        $query_for_pwd_backup = "INSERT INTO zc_legacy_passwords (id, email, password, updated) VALUES ($id_customer, '$email', '$password',0)";
        $prestashop_db->query($query_for_pwd_backup);
    }
}


echo "Migration completed.";

$oscommerce_db->close();
$prestashop_db->close();
?>