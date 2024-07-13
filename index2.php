<?php
// Database connection settings for osCommerce and PrestaShop
$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop');


if ($oscommerce_db->connect_error) {
    die("Connection to osCommerce database failed: " . $oscommerce_db->connect_error);
}
if ($prestashop_db->connect_error) {
    die("Connection to PrestaShop database failed: " . $prestashop_db->connect_error);
}
echo "Connected to both databases successfully.\n";

function formatDatetime($datetime) {
    try {
        $dt = new DateTime($datetime);
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

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



// Migrate customers, addresses, and groups
$result = $oscommerce_db->query("
    SELECT c.customers_id, c.customers_gender, c.customers_firstname, c.customers_lastname, c.customers_dob, 
           c.customers_email_address, c.customers_password, c.customers_date_added, c.customers_last_modified, 
           a.entry_gender, a.entry_firstname, a.entry_lastname, a.entry_street_address, a.entry_postcode, a.entry_city, a.entry_country_id,
           g.customers_group_id
    FROM customers c 
    JOIN address_book a ON c.customers_id = a.customers_id
    LEFT JOIN customers_group g ON c.customers_id = g.customers_id
");
if (!$result) {
    die("Error retrieving customers from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $email = $prestashop_db->real_escape_string($row['customers_email_address']);
    $password = $prestashop_db->real_escape_string($row['customers_password']);
    $firstname = $prestashop_db->real_escape_string($row['customers_firstname']);
    $lastname = $prestashop_db->real_escape_string($row['customers_lastname']);
    $date_add = formatDatetime($row['customers_date_added']);
    $date_upd = formatDatetime($row['customers_last_modified']);
    $id_gender = mapGender($row['customers_gender']);
    $id_default_group = $row['customers_group_id'] ? (int)$row['customers_group_id'] : 1; // Default group if none assigned

    if ($date_add && $date_upd) {
        $query = "INSERT INTO ps_customer (email, passwd, firstname, lastname, date_add, date_upd, id_gender, id_default_group) 
                  VALUES ('$email', '$password', '$firstname', '$lastname', '$date_add', '$date_upd', '$id_gender', '$id_default_group')";

        if (!$prestashop_db->query($query)) {
            echo "Error inserting customer: " . $prestashop_db->error . "\n";
        } else {
            echo "Customer $firstname $lastname migrated successfully.\n";
        }
    } else {
        echo "Invalid date format for customer $firstname $lastname.\n";
    }
}

$oscommerce_db->close();
$prestashop_db->close();

echo "Migration completed successfully!";
?>
