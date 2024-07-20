<?php
require_once 'db.php';
// Migrate categories
$result = $oscommerce_db->query("SELECT * FROM categories");
if (!$result) {
    die("Error retrieving categories from osCommerce: " . $oscommerce_db->error);
}
echo "<style>
span {
  display: inline-block;
}</style>";

while ($row = $result->fetch_assoc()) {
    $id_category = $prestashop_db->real_escape_string($row['categories_id']);
    $id_parent = $prestashop_db->real_escape_string($row['parent_id']);
    $id_shop_default = 1;
    $level_depth = 0;
    $nleft = 1;
    $nright = 1;
    $active = 1;
    $is_root_category = 0;
    $position = $prestashop_db->real_escape_string($row['sort_order']);
    $date_add = $prestashop_db->real_escape_string($row['date_added']);
    $date_upd = $prestashop_db->real_escape_string($row['last_modified']);

    $query = "INSERT INTO `ps_category` (`id_category`, `id_parent`, `id_shop_default`, `level_depth`, `nleft`, `nright`, `active`, `date_add`, `date_upd`, `position`, `is_root_category`)
            VALUES ('$id_category', '$id_parent', '$id_shop_default', '$level_depth', '$nleft', '$nright', '$active', '$date_add', '$date_upd', '$position', '$is_root_category')";

    if (!$prestashop_db->query($query)) {
        echo "Error inserting category: " . $prestashop_db->error . "\n";
    } else {
        echo "Category ID $id_category migrated successfully.\n";
    }
}
echo "<span style='color:green'>All Categories migrated successfully.</span><br>";
echo "<hr>";
echo "<span style='color:green'><b>Starting Category meta import ...</b></span><br>";

// Migrate categories name title and meta
$result1 = $oscommerce_db->query("SELECT * FROM categories_description");
if (!$result1) {
    die("Error retrieving categoriy meta from osCommerce: " . $oscommerce_db->error);
}


while ($row = $result1->fetch_assoc()) {
    $id_category = $prestashop_db->real_escape_string($row['categories_id']);
    $id_shop = 1;
    $id_lang = $prestashop_db->real_escape_string($row['language_id']);
    $category_name = $prestashop_db->real_escape_string($row['categories_name']);
    $description = $prestashop_db->real_escape_string($row['categories_text']);
    $link_rewrite = '';


    $query1 = "INSERT INTO `ps_category_lang` (`id_category`, `id_shop`, `id_lang`, `name`, `description`, `link_rewrite`) 
VALUES ('$id_category', '1$id_shop', '$id_lang', '$category_name', '$description', '$link_rewrite')";

    if (!$prestashop_db->query($query1)) {
        echo "Error inserting category Meta: " . $prestashop_db->error . "\n";
    } else {
        echo "Category ID $id_category meta migrated successfully.\n";

    }
}


echo "<span style='color:green'>All Category meta migrated successfully.</span><br>";
echo "<hr>";
echo "<span style='color:pink'><b>Starting Category Product Link  import ...</b></span><br>";

// Migrate categories name title and meta
$result2 = $oscommerce_db->query("SELECT * FROM products_to_categories");
if (!$result2) {
    die("Error retrieving categoriy meta from osCommerce: " . $oscommerce_db->error);
}


while ($row = $result2->fetch_assoc()) {
    $id_category = $prestashop_db->real_escape_string($row['categories_id']);
    $id_product = $prestashop_db->real_escape_string($row['products_id']);
    $position = 0;


    $query2 = "INSERT INTO `ps_category_product` (`id_category`, `id_product`, `position`) VALUES
('$id_category', '$id_product', '$position')";

    if (!$prestashop_db->query($query2)) {
        echo "Error inserting category product Link: " . $prestashop_db->error . "\n";
    } else {
        echo "Category ID $id_category linked to  $id_product migrated successfully.\n";
    }
}
    
require_once 'dbclose.php';