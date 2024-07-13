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
echo "Connected to both databases successfully.\n <br/>";

function formatDatetime($datetime) {
    try {
        $dt = new DateTime($datetime);
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

// Function to get currency ID based on currency code
function getCurrencyId($currency_code, $prestashop_db) {
    $result = $prestashop_db->query("SELECT id_currency FROM ps_currency WHERE iso_code = '$currency_code'");
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['id_currency'];
    }
    return 0; // Default currency ID if not found
}

// Migrate addresses
$address_mapping = []; // To keep track of osCommerce address IDs to PrestaShop address IDs
$result = $oscommerce_db->query("SELECT * FROM address_book");
if (!$result) {
    die("Error retrieving addresses from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $firstname = $prestashop_db->real_escape_string($row['entry_firstname']);
    $lastname = $prestashop_db->real_escape_string($row['entry_lastname']);
    $address1 = $prestashop_db->real_escape_string($row['entry_street_address']);
    $postcode = $prestashop_db->real_escape_string($row['entry_postcode']);
    $city = $prestashop_db->real_escape_string($row['entry_city']);
    $country_id = (int)$row['entry_country_id'];
    $date_add = formatDatetime(date('Y-m-d H:i:s'));
    $date_upd = $date_add;

    $alias = "Default Alias"; // Example of a default value
    

    $query = "INSERT INTO ps_address (id_country, firstname, lastname, address1, postcode, city, date_add, date_upd, alias)
              VALUES ('$country_id', '$firstname', '$lastname', '$address1', '$postcode', '$city', '$date_add', '$date_upd', '$alias')";

    if ($prestashop_db->query($query)) {
        $address_mapping[$row['address_book_id']] = $prestashop_db->insert_id;
    } else {
        echo "Error inserting address: " . $prestashop_db->error . "\n";
    }
}

// Default carrier ID, language ID, cart ID, and address IDs (update as necessary)
$default_carrier_id = 1;
$default_lang_id = 1;
$default_cart_id = 0;  // You may want to change this to a valid cart ID if available
$default_address_id = 1;  // You may want to change this to a valid address ID if available
$id_shop = 1; // Replace with the appropriate id_shop value for your context
$product_weight = 0.0;
$tax_name = 'N/A';

// Migrate orders
$result = $oscommerce_db->query("SELECT * FROM orders");
if (!$result) {
    die("Error retrieving orders from osCommerce: " . $oscommerce_db->error);
}
while ($row = $result->fetch_assoc()) {
    $id_customer = (int)$row['customers_id'];
    $current_state = (int)$row['orders_status'];
    $date_add = formatDatetime($row['date_purchased']);
    $date_upd = formatDatetime($row['last_modified']);
    $total_paid = (float)$row['currency_value']; // Adjust this based on how you calculate the total paid
    $currency_code = $row['currency']; // Assuming 'currency' column contains the currency code
    $id_currency = getCurrencyId($currency_code, $prestashop_db);

    $id_address_delivery = isset($address_mapping[$row['delivery_address_id']]) ? $address_mapping[$row['delivery_address_id']] : $default_address_id;
    $id_address_invoice = isset($address_mapping[$row['billing_address_id']]) ? $address_mapping[$row['billing_address_id']] : $default_address_id;

    $invoice_date = formatDatetime(date('Y-m-d H:i:s'));
    $delivery_date  = formatDatetime(date('Y-m-d H:i:s'));

    if ($date_add && $date_upd) {
        $query = "INSERT INTO ps_orders (id_customer, current_state, date_add, date_upd, total_paid, total_paid_real, total_products, total_shipping, payment, module, id_carrier, id_lang, id_cart, id_currency, id_address_delivery, id_address_invoice, invoice_date, delivery_date)
                  VALUES ('$id_customer', '$current_state', '$date_add', '$date_upd', '$total_paid', '$total_paid', '$total_paid', 0, 'N/A', 'N/A', '$default_carrier_id', '$default_lang_id', '$default_cart_id', '$id_currency', '$id_address_delivery', '$id_address_invoice', '$invoice_date', '$delivery_date')";

        if (!$prestashop_db->query($query)) {
            echo "Error inserting order: " . $prestashop_db->error . "\n";
        } else {
            $id_order = $prestashop_db->insert_id;
            echo "Order ID $id_order migrated successfully.\n <br/>";

            // Migrate order details
            $result_details = $oscommerce_db->query("SELECT * FROM orders_products WHERE orders_id = " . $row['orders_id']);
            if ($result_details) {
                while ($detail = $result_details->fetch_assoc()) {
                    $product_id = (int)$detail['products_id'];
                    $product_name = $prestashop_db->real_escape_string($detail['products_name']);
                    $product_price = (float)$detail['products_price'];
                    $product_quantity = (int)$detail['products_quantity'];

                    $query_detail = "INSERT INTO ps_order_detail (id_order, product_id, product_name, product_price, product_quantity, product_weight, tax_name, id_shop)
                                     VALUES ('$id_order', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_weight', '$tax_name', '$id_shop')";

                    if (!$prestashop_db->query($query_detail)) {
                        echo "Error inserting order detail: " . $prestashop_db->error . "\n";
                    } else {
                        echo "Order detail for order ID $id_order migrated successfully.\n <br/>";
                    }
                }
            }

            // Migrate order total
            $result_total = $oscommerce_db->query("SELECT * FROM orders_total WHERE orders_id = " . $row['orders_id']);
            if ($result_total) {
                while ($total = $result_total->fetch_assoc()) {
                    $class = $prestashop_db->real_escape_string($total['class']);
                    $value = (float)$total['value'];

                    // Determine total_paid and total_shipping based on class type
                    if ($class == 'ot_total') {
                        $total_paid = $value;
                    } elseif ($class == 'ot_shipping') {
                        $total_shipping = $value;
                    }

                    $query_total = "INSERT INTO ps_order_detail (id_order, class, value)
                                    VALUES ('$id_order', '$class', '$value')";

                    if (!$prestashop_db->query($query_total)) {
                        echo "Error inserting order total: " . $prestashop_db->error . "\n";
                    } else {
                        echo "Order total for order ID $id_order migrated successfully.\n <br/>";
                    }
                }
            }

            // Migrate order history
            $result_history = $oscommerce_db->query("SELECT * FROM orders_status_history WHERE orders_id = " . $row['orders_id']);
            if ($result_history) {
                while ($history = $result_history->fetch_assoc()) {
                    $id_order_state = (int)$history['orders_status_id'];
                    $date_add = formatDatetime($history['date_added']);
                    $comments = $prestashop_db->real_escape_string($history['comments']);

                    $query_history = "INSERT INTO ps_order_history (id_order, id_order_state, date_add, comments)
                                      VALUES ('$id_order', '$id_order_state', '$date_add', '$comments')";

                    if (!$prestashop_db->query($query_history)) {
                        echo "Error inserting order history: " . $prestashop_db->error . "\n";
                    } else {
                        echo "Order history for order ID $id_order migrated successfully.\n <br/>";
                    }
                }
            }
        }
    } else {
        echo "Invalid date format for order ID " . $row['orders_id'] . ".\n";
    }
}

$oscommerce_db->close();
$prestashop_db->close();

echo "Migration completed successfully!";
?>