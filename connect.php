<?php
$oscommerce_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_oscommerce');
$prestashop_db = new mysqli('127.0.0.1:8889', 'root', 'root', 'test_lamali_prestashop');

if ($oscommerce_db->connect_error) {
    die("Connection to osCommerce database failed: " . $oscommerce_db->connect_error);
}
if ($prestashop_db->connect_error) {
    die("Connection to PrestaShop database failed: " . $prestashop_db->connect_error);
}
echo "Connected to both databases successfully.\n";
?>
<?php
// Migrate customers
$result = $oscommerce_db->query("SELECT * FROM customers");
if (!$result) {
    die("Error retrieving customers from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $email = $prestashop_db->real_escape_string($row['customers_email_address']);
    $password = $prestashop_db->real_escape_string($row['customers_password']);
    $firstname = $prestashop_db->real_escape_string($row['customers_firstname']);
    $lastname = $prestashop_db->real_escape_string($row['customers_lastname']);
    $date_add = $prestashop_db->real_escape_string($row['customers_date_added']);

    $query = "INSERT INTO ps_customer (email, passwd, firstname, lastname, date_add, date_upd) 
              VALUES ('$email', '$password', '$firstname', '$lastname', '$date_add', '$date_add')";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting customer: " . $prestashop_db->error . "\n";
    } else {
        echo "Customer $firstname $lastname migrated successfully.\n";
    }
}
?>
