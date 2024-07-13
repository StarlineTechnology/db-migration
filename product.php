<?php
// Migrate products
$result = $oscommerce_db->query("SELECT * FROM products");
if (!$result) {
    die("Error retrieving products from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $id_product = $prestashop_db->real_escape_string($row['products_id']);
    $price = $prestashop_db->real_escape_string($row['products_price']);
    $date_add = $prestashop_db->real_escape_string($row['products_date_added']);
    $date_upd = $prestashop_db->real_escape_string($row['products_last_modified']);

    $query = "INSERT INTO ps_product (id_product, price, date_add, date_upd) 
              VALUES ('$id_product', '$price', '$date_add', '$date_upd')";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting product: " . $prestashop_db->error . "\n";
    } else {
        echo "Product ID $id_product migrated successfully.\n";
    }
}
?>
