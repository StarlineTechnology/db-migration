<?php
require_once "db.php";

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
//Step 1: Migrate Address
$address_mapping = []; // To keep track of osCommerce address IDs to PrestaShop address IDs
$result = $oscommerce_db->query("SELECT * FROM address_book");
if (!$result) {
    die("<span style='color:red'>Error retrieving addresses from osCommerce: " . $oscommerce_db->error."</span>");
}
while ($row = $result->fetch_assoc()) {

    $id_address =  $prestashop_db->real_escape_string($row['address_book_id']);
    $id_country = (int)$prestashop_db->real_escape_string($row['entry_country_id']); 
    $id_state = (int)$prestashop_db->real_escape_string($row['entry_state']); 
    $id_customer = $prestashop_db->real_escape_string($row['customers_id']); 
    $id_manufacturer = 0; 
    $id_supplier = 0 ;
    $id_warehouse = 0;
    $alias = 'Default Alias'; 
    $company = $prestashop_db->real_escape_string($row['entry_company']); 
    $firstname = $prestashop_db->real_escape_string($row['entry_firstname']); 
    $lastname = $prestashop_db->real_escape_string($row['entry_lastname']); 
    $address1 = $prestashop_db->real_escape_string($row['entry_street_address']); 
    $address2 = NULL;
    $postcode = $prestashop_db->real_escape_string($row['entry_postcode']); 
    $city = $prestashop_db->real_escape_string($row['entry_city']); 
    $other = NULL;
    $phone = NULL;
    $phone_mobile = NULL;
    $vat_number = NULL; 
    $dni = NULL;
    $date_add = formatDatetime(date('Y-m-d H:i:s'));
    $date_upd = $date_add;
    $active = 1;
    $deleted = 0;

    
    $query1 = "INSERT INTO `ps_address` (`id_address`, `id_country`, `id_state`, `id_customer`, `id_manufacturer`, `id_supplier`, `id_warehouse`, `alias`, `company`, `firstname`, `lastname`, `address1`, `address2`, `postcode`, `city`, `other`, `phone`, `phone_mobile`, `vat_number`, `dni`, `date_add`, `date_upd`, `active`, `deleted`) 
                    VALUES('$id_address', '$id_country', '$id_state', '$id_customer', '$id_manufacturer', '$id_supplier', '$id_warehouse', '$alias', '$company', '$firstname', '$lastname', '$address1', '$address2', '$postcode', '$city', '$other', '$phone', '$phone_mobile', '$vat_number', '$dni', '$date_add', '$date_upd', '$active', '$deleted')";

if ($prestashop_db->query($query1)) {
        $address_mapping[$row['address_book_id']] = $prestashop_db->insert_id;

        echo "<span style='color:green'>Address ID $id_address migrated successfully.</span><br>";
    } else {
        echo "<span style='color:red'>Error inserting address: " . $prestashop_db->error . "</span>\n";
    }
}

function insert_address($row,$prestashop_db){
    $id_country = (int)$prestashop_db->real_escape_string($row['entry_country_id']); 
    $id_state = (int)$prestashop_db->real_escape_string($row['entry_state']); 
    $id_customer = $prestashop_db->real_escape_string($row['customers_id']); 
    $id_manufacturer = 0; 
    $id_supplier = 0 ;
    $id_warehouse = 0;
    $alias = 'Default Alias'; 
    $company = $prestashop_db->real_escape_string($row['entry_company']); 
    $firstname = $prestashop_db->real_escape_string($row['entry_firstname']); 
    $lastname = $prestashop_db->real_escape_string($row['entry_lastname']); 
    $address1 = $prestashop_db->real_escape_string($row['entry_street_address']); 
    $address2 = NULL;
    $postcode = $prestashop_db->real_escape_string($row['entry_postcode']); 
    $city = $prestashop_db->real_escape_string($row['entry_city']); 
    $other = NULL;
    $phone = NULL;
    $phone_mobile = NULL;
    $vat_number = NULL; 
    $dni = NULL;
    $date_add = formatDatetime(date('Y-m-d H:i:s'));
    $date_upd = $date_add;
    $active = 1;
    $deleted = 0;

    
    $query1 = "INSERT INTO `ps_address` (`id_country`, `id_state`, `id_customer`, `id_manufacturer`, `id_supplier`, `id_warehouse`, `alias`, `company`, `firstname`, `lastname`, `address1`, `address2`, `postcode`, `city`, `other`, `phone`, `phone_mobile`, `vat_number`, `dni`, `date_add`, `date_upd`, `active`, `deleted`) 
                    VALUES('$id_country', '$id_state', '$id_customer', '$id_manufacturer', '$id_supplier', '$id_warehouse', '$alias', '$company', '$firstname', '$lastname', '$address1', '$address2', '$postcode', '$city', '$other', '$phone', '$phone_mobile', '$vat_number', '$dni', '$date_add', '$date_upd', '$active', '$deleted')";
$address_ID = 0;
if ($prestashop_db->query($query1)) {
    $address_ID = $prestashop_db->insert_id;
        echo "<span style='color:green'>Address ID $address_ID migrated successfully.</span><br>";
    } else {
        echo "<span style='color:red'>Error inserting address: " . $prestashop_db->error . "</span>\n";
    }

    return $address_ID;

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
    die("<span style='color:red'>Error retrieving orders from osCommerce: " . $oscommerce_db->error ."</span>");
}

while ($row = $result->fetch_assoc()) {

    $delivery_data['entry_country_id'] = $row['delivery_country'];
    $delivery_data['entry_state'] = $row['delivery_state'];
    $delivery_data['customers_id'] = $row['customers_id'];
    $delivery_data['entry_company'] = $row['delivery_company'];
    $delivery_data['entry_firstname'] = $row['delivery_name'];
    $delivery_data['entry_lastname'] = '';
    $delivery_data['entry_street_address'] = $row['delivery_street_address'];
    $delivery_data['entry_postcode'] = $row['delivery_postcode'];
    $delivery_data['entry_city'] = $row['delivery_city'];

    $delivery_address_id = insert_address($delivery_data,$prestashop_db);


    $billing_data['entry_country_id'] = $row['billing_country'];
    $billing_data['entry_state'] = $row['billing_state'];
    $billing_data['customers_id'] = $row['customers_id'];
    $billing_data['entry_company'] = $row['billing_company'];
    $billing_data['entry_firstname'] = $row['billing_name'];
    $billing_data['entry_lastname'] = '';
    $billing_data['entry_street_address'] = $row['billing_street_address'];
    $billing_data['entry_postcode'] = $row['billing_postcode'];
    $billing_data['entry_city'] = $row['billing_city'];

    $billing_address_id = insert_address($billing_data,$prestashop_db);

    $invoice_date = formatDatetime(date('Y-m-d H:i:s'));
    $delivery_date  = formatDatetime(date('Y-m-d H:i:s'));

    $id_customer = (int)$row['customers_id'];
    $current_state = (int)$row['orders_status'];
    $date_add = formatDatetime($row['date_purchased']);
    $date_upd = formatDatetime($row['last_modified']);
    $total_paid = (float)$row['currency_value']; // Adjust this based on how you calculate the total paid
    $currency_code = $row['currency']; // Assuming 'currency' column contains the currency code
    $id_currency = getCurrencyId($currency_code, $prestashop_db);

    $id_order = $prestashop_db->real_escape_string($row['orders_id']);  
    $reference = NULL;  
    $id_shop_group = 1; 
    $id_shop = 1; 
    $id_carrier = 1; 
    $id_lang = 1; 
    $id_customer = $prestashop_db->real_escape_string($row['customers_id']);  
    $id_cart = 0;  
    $id_currency = getCurrencyId($row['currency'], $prestashop_db); 
    $id_address_delivery = $delivery_address_id; 
    $id_address_invoice = $billing_address_id; 
    $current_state = $prestashop_db->real_escape_string($row['orders_status']);  
    $secure_key = '-1';  
    $payment = $prestashop_db->real_escape_string($row['payment_method']);  
    $conversion_rate = '1.000000';  
    $module = 'N/A';  
    $recyclable = 0;  
    $gift = 0;  
    $gift_message = '';  
    $mobile_theme = 0;  
    $shipping_number = $prestashop_db->real_escape_string($row['customers_telephone']);  
    $total_discounts = '0.000000';  
    $total_discounts_tax_incl = '0.000000'; 
    $total_discounts_tax_excl = '0.000000';  
    $total_paid = '0.000000';  
    $total_paid_tax_incl = '0.000000';  
    $total_paid_tax_excl = '0.000000';  
    $total_paid_real = '0.000000';  
    $total_products = '0.000000';  
    $total_products_wt = '0.000000';  
    $total_shipping = '0.000000';  
    $total_shipping_tax_incl = '0.000000';  
    $total_shipping_tax_excl = '0.000000';  
    $carrier_tax_rate = '0.000000';  
    $total_wrapping = '0.000000';  
    $total_wrapping_tax_incl = '0.000000';  
    $total_wrapping_tax_excl = '0.000000';  
    $round_mode = 2;  
    $round_type =1;  
    $invoice_number = 0;  
    $delivery_number = 0;  
    $invoice_date = $invoice_date;  
    $delivery_date = $delivery_date;  
    $valid = 1;  
    $date_add = formatDatetime($row['date_purchased']);
    $date_upd = formatDatetime($row['last_modified']); 

    //$id_address_delivery = isset($address_mapping[$row['delivery_address_id']]) ? $address_mapping[$row['delivery_address_id']] : $default_address_id;
   // $id_address_invoice = isset($address_mapping[$row['billing_address_id']]) ? $address_mapping[$row['billing_address_id']] : $default_address_id;

    

    if ($date_add && $date_upd) {
        $query = "INSERT INTO ps_orders (id_customer, current_state, date_add, date_upd, total_paid, total_paid_real, total_products, total_shipping, payment, module, id_carrier, id_lang, id_cart, id_currency, id_address_delivery, id_address_invoice, invoice_date, delivery_date)
                  VALUES ('$id_customer', '$current_state', '$date_add', '$date_upd', '$total_paid', '$total_paid', '$total_paid', 0, 'N/A', 'N/A', '$default_carrier_id', '$default_lang_id', '$default_cart_id', '$id_currency', '$id_address_delivery', '$id_address_invoice', '$invoice_date', '$delivery_date')";

        $query2 = "INSERT INTO `ps_orders` (`id_order`, `reference`, `id_shop_group`, `id_shop`, `id_carrier`, `id_lang`, `id_customer`, `id_cart`, `id_currency`, `id_address_delivery`, `id_address_invoice`, `current_state`, `secure_key`, `payment`, `conversion_rate`, `module`, `recyclable`, `gift`, `gift_message`, `mobile_theme`, `shipping_number`, `total_discounts`, `total_discounts_tax_incl`, `total_discounts_tax_excl`, `total_paid`, `total_paid_tax_incl`, `total_paid_tax_excl`, `total_paid_real`, `total_products`, `total_products_wt`, `total_shipping`, `total_shipping_tax_incl`, `total_shipping_tax_excl`, `carrier_tax_rate`, `total_wrapping`, `total_wrapping_tax_incl`, `total_wrapping_tax_excl`, `round_mode`, `round_type`, `invoice_number`, `delivery_number`, `invoice_date`, `delivery_date`, `valid`, `date_add`, `date_upd`) 
                    VALUES('$id_order', '$reference', '$id_shop_group', '$id_shop', '$id_carrier', '$id_lang', '$id_customer', '$id_cart', '$id_currency', '$id_address_delivery', '$id_address_invoice', '$current_state', '$secure_key', '$payment', '$conversion_rate', '$module', '$recyclable', '$gift', '$gift_message', '$mobile_theme', '$shipping_number', '$total_discounts', '$total_discounts_tax_incl', '$total_discounts_tax_excl', '$total_paid', '$total_paid_tax_incl', '$total_paid_tax_excl', '$total_paid_real', '$total_products', '$total_products_wt', '$total_shipping', '$total_shipping_tax_incl', '$total_shipping_tax_excl', '$carrier_tax_rate', '$total_wrapping', '$total_wrapping_tax_incl', '$total_wrapping_tax_excl', '$round_mode', '$round_type', '$invoice_number', '$delivery_number', '$invoice_date', '$delivery_date', '$valid', '$date_add', '$date_upd')";

        if (!$prestashop_db->query($query2)) {
            echo "<span style='color:red'>Error inserting order: " . $prestashop_db->error . "</span>\n";
        } else {
            $id_order = $prestashop_db->insert_id;
            echo "<span style='color:green'>Order ID $id_order migrated successfully.</span>\n <br/>";


            // Migrate order details
            $result_details = $oscommerce_db->query("SELECT * FROM orders_products WHERE orders_id = " . $row['orders_id']);
            if ($result_details) {
                while ($detail = $result_details->fetch_assoc()) {
                    //$order_id = (int)$detail['orders_id'];
                    $product_id = (int)$detail['products_id'];
                    $product_name = $prestashop_db->real_escape_string($detail['products_name']);
                    $product_price = (float)$detail['products_price'];
                    $product_quantity = (int)$detail['products_quantity'];

                    $query_detail = "INSERT INTO ps_order_detail (id_order, product_id, product_name, product_price, product_quantity, product_weight, tax_name, id_shop)
                                     VALUES ('$id_order', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_weight', '$tax_name', '$id_shop')";

                    if (!$prestashop_db->query($query_detail)) {
                        echo "<span style='color:red'>Error inserting order detail: " . $prestashop_db->error . "</span>\n";
                    } else {
                        echo "<span style='color:green'>Order detail for order ID $id_order migrated successfully.</span>\n <br/>";
                    }
                }
            }

            // Migrate order total
            $result_total = $oscommerce_db->query("SELECT * FROM orders_total WHERE orders_id = " . $row['orders_id']);
            $total_price_tax_incl = 0;
            $total_price_tax_excl = 0;
            $unit_price_tax_incl = 0;
            $unit_price_tax_excl = 0;
            $total_shipping_price_tax_incl = 0;
            $total_shipping_price_tax_excl = 0;

            if ($result_total) {
                while ($total = $result_total->fetch_assoc()) {
                    $class = $prestashop_db->real_escape_string($total['class']);
                    $value = (float)$total['value'];
                    $field = '';
                    // Determine total_paid and total_shipping based on class type
                    if ($class == 'ot_total') {
                        $field = 'total_price_tax_incl';
                    } elseif ($class == 'ot_shipping') {
                        $field = 'total_shipping_price_tax_excl';
                    }elseif($class == 'ot_subtotal'){
                        $field = 'total_price_tax_excl';
                    }elseif($class == 'ot_tax'){
                        $field = '';
                    }

                    if($field){
                        $query_total = "UPDATE  ps_order_detail SET $field = $value WHERE `id_order` = '$id_order'";

                        if (!$prestashop_db->query($query_total)) {
                            echo "<span style='color:red'>Error Updating order total: " . $prestashop_db->error . "</span>\n";
                        } else {
                            echo "<span style='color:green'>Order total for order ID $id_order field = $field migrated successfully.</span>\n <br/>";
                        }
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

                    $query_history = "INSERT INTO ps_order_history (id_employee,id_order, id_order_state, date_add)
                                      VALUES (0,'$id_order', '$id_order_state', '$date_add')";

                    if (!$prestashop_db->query($query_history)) {
                        echo "<span style='color:red'>Error inserting order history: " . $prestashop_db->error . "</span>\n";
                    } else {
                        echo "<span style='color:green'>Order history for order ID $id_order migrated successfully.</span>\n <br/>";
                    }
                }
            }
        }
    } else {
        echo "<span style='color:red'>Invalid date format for order ID " . $row['orders_id'] . ".</span>\n";
    }

/**
 * Get the value of prestashop_db */ 
 function getPrestashop_db()
{
return $this->prestashop_db;
}

/**
 * Set the value of prestashop_db
 *
 * @return  self
 */ 
function setPrestashop_db($prestashop_db)
{
$this->prestashop_db = $prestashop_db;

return $this;
}
}

//Step 1: Migrate Orders


require_once "dbclose.php";

echo "<span>Migration completed successfully!</span>";