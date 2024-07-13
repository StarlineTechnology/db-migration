<?php
// Migrate categories
$result = $oscommerce_db->query("SELECT * FROM categories");
if (!$result) {
    die("Error retrieving categories from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $id_category = $prestashop_db->real_escape_string($row['categories_id']);
    $date_add = $prestashop_db->real_escape_string($row['date_added']);
    $date_upd = $prestashop_db->real_escape_string($row['last_modified']);

    $query = "INSERT INTO ps_category (id_category, date_add, date_upd) 
              VALUES ('$id_category', '$date_add', '$date_upd')";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting category: " . $prestashop_db->error . "\n";
    } else {
        echo "Category ID $id_category migrated successfully.\n";
    }
}
?>
