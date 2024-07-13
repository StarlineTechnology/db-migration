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
