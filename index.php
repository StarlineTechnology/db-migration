<?php
$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop');


// Check connections
if ($oscommerce_db->connect_error) die("osCommerce connection failed: " . $oscommerce_db->connect_error);
if ($prestashop_db->connect_error) die("PrestaShop connection failed: " . $prestashop_db->connect_error);

echo "Connected to both databases successfully.<br>";

// Function to escape and format values for SQL
function escape($db, $value) {
    return $db->real_escape_string($value);
}

// Migrate customers
$customers = $oscommerce_db->query("
                                    SELECT c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_email_address, c.customers_login_allowed, c.customers_password, c.customers_telephone, c.customers_fax, a.entry_company, a.entry_company_tax_id, a.entry_street_address, a.entry_postcode, a.entry_city, a.entry_country_id, ci.customers_info_date_account_created
                                    FROM customers c 
                                    JOIN address_book a ON c.customers_id = a.customers_id
                                    LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id");

if ($customers === false) {
    die("Error fetching customers: " . $oscommerce_db->error);
}

echo "Migrating customers...<br>";

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
    $date_add = escape($prestashop_db, $customer['customers_info_date_account_created']);
    $id_gender = mapGender($row['customers_gender']);
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
              VALUES ($id_customer, $id_gender, '$firstname', '$lastname', '$email', MD5('$password'), '$date_add', '$date_upd', '$active', '$company', '$siret', '$company', '$taxid', '$street', '$post', '$city', '$country', '$telephone', '$fax')";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting customer ID $id_customer: " . $prestashop_db->error . "<br>";
    } else {
        echo "Customer ID $id_customer migrated successfully.<br>";
    }
}

// Migrate addresses
$addresses = $oscommerce_db->query("SELECT ab.address_book_id, ab.customers_id, ab.entry_firstname, ab.entry_lastname, ab.entry_street_address, ab.entry_postcode, ab.entry_city, ab.entry_country_id 
                                    FROM address_book ab");

if ($addresses === false) {
    die("Error fetching addresses: " . $oscommerce_db->error);
}

echo "Migrating addresses...<br>";
while ($address = $addresses->fetch_assoc()) {
    $id_address = $address['address_book_id'];
    $id_customer = $address['customers_id'];
    $firstname = escape($prestashop_db, $address['entry_firstname']);
    $lastname = escape($prestashop_db, $address['entry_lastname']);
    $address1 = escape($prestashop_db, $address['entry_street_address']);
    $postcode = escape($prestashop_db, $address['entry_postcode']);
    $city = escape($prestashop_db, $address['entry_city']);
    $id_country = $address['entry_country_id'];
    
    $query = "INSERT INTO ps_address (id_address, id_customer, firstname, lastname, address1, postcode, city, id_country, date_add)
              VALUES ($id_address, $id_customer, '$firstname', '$lastname', '$address1', '$postcode', '$city', $id_country, NOW())";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting address ID $id_address: " . $prestashop_db->error . "<br>";
    } else {
        echo "Address ID $id_address migrated successfully.<br>";
    }
}

// Optional: Migrate customer groups if relevant to your setup
$customer_groups = $oscommerce_db->query("SELECT customers_id, customers_groups_id FROM customers_groups");
if ($customer_groups === false) {
    die("Error fetching customer groups: " . $oscommerce_db->error);
}

echo "Migrating customer groups...<br>";
while ($group = $customer_groups->fetch_assoc()) {
    $id_customer = $group['customers_id'];
    $id_group = $group['customers_groups_id'];

    $query = "INSERT INTO ps_customer_group (id_customer, id_group)
              VALUES ($id_customer, $id_group)";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting customer group ID $id_group for customer ID $id_customer: " . $prestashop_db->error . "<br>";
    } else {
        echo "Customer group ID $id_group for customer ID $id_customer migrated successfully.<br>";
    }
}

echo "Migration completed.";

$oscommerce_db->close();
$prestashop_db->close();
?>
