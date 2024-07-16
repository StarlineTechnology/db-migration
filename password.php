<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Database connection details for osCommerce and PrestaShop
$oscommerce_db_host = '127.0.0.1:8889';
$oscommerce_db_name = 'test_lamali_oscommerce';
$oscommerce_db_user = 'root';
$oscommerce_db_pass = 'root';

$prestashop_db_host = '127.0.0.1:8889';
$prestashop_db_name = 'test_lamali_prestashop';
$prestashop_db_user = 'root';
$prestashop_db_pass = 'root';



// Custom password generation function (not needed if using bcrypt)
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Connect to osCommerce database
$oscommerce_conn = new mysqli($oscommerce_db_host, $oscommerce_db_user, $oscommerce_db_pass, $oscommerce_db_name);
if ($oscommerce_conn->connect_error) {
    die("osCommerce Connection failed: " . $oscommerce_conn->connect_error);
}
echo "Connected to osCommerce database successfully.<br>";

// Connect to PrestaShop database
$prestashop_conn = new mysqli($prestashop_db_host, $prestashop_db_user, $prestashop_db_pass, $prestashop_db_name);
if ($prestashop_conn->connect_error) {
    die("PrestaShop Connection failed: " . $prestashop_conn->connect_error);
}
echo "Connected to PrestaShop database successfully.<br>";

// Fetch osCommerce customers
$oscommerce_sql = "SELECT c.customers_id, c.customers_email_address, c.customers_password, c.customers_firstname, c.customers_lastname, c.customers_gender, a.entry_street_address, a.entry_postcode, a.entry_city, a.entry_country_id
                   FROM customers c
                   LEFT JOIN address_book a ON c.customers_id = a.customers_id";
$oscommerce_result = $oscommerce_conn->query($oscommerce_sql);

if ($oscommerce_result->num_rows > 0) {
    while ($row = $oscommerce_result->fetch_assoc()) {
        $customer_id = $row['customers_id'];
        $customer_email = $row['customers_email_address'];
        $oscommerce_password = $row['customers_password'];
        $customer_firstname = $row['customers_firstname'];
        $customer_lastname = $row['customers_lastname'];
        $customer_gender = $row['customers_gender'];
        $street_address = $row['entry_street_address'];
        $postcode = $row['entry_postcode'];
        $city = $row['entry_city'];
        $country_id = $row['entry_country_id'];

        // Map gender to id_gender
        $id_gender = ($customer_gender == 'm' || $customer_gender == 'M') ? 1 : 2;

        // Split osCommerce password into hash and salt
        list($oscommerce_hash, $oscommerce_salt) = explode(':', $oscommerce_password);

        // Generate PrestaShop password using bcrypt
        $prestashop_password = password_hash($oscommerce_hash, PASSWORD_BCRYPT);

        // Check if customer already exists in PrestaShop
        $check_customer_sql = "SELECT id_customer FROM ps_customer WHERE email = '$customer_email'";
        $check_customer_result = $prestashop_conn->query($check_customer_sql);

        if ($check_customer_result->num_rows > 0) {
            // Update existing customer
            $update_customer_sql = "UPDATE ps_customer SET firstname = '$customer_firstname', lastname = '$customer_lastname', id_gender = '$id_gender', passwd = '$prestashop_password' WHERE email = '$customer_email'";
            if ($prestashop_conn->query($update_customer_sql) === TRUE) {
                echo "Customer ID $customer_id updated successfully.<br>";
            } else {
                echo "Error updating customer ID $customer_id: " . $prestashop_conn->error . "<br>";
            }
        } else {
            // Insert new customer
            $insert_customer_sql = "INSERT INTO ps_customer (email, firstname, lastname, id_gender, passwd, date_add, date_upd) VALUES ('$customer_email', '$customer_firstname', '$customer_lastname', '$id_gender', '$prestashop_password', NOW(), NOW())";
            if ($prestashop_conn->query($insert_customer_sql) === TRUE) {
                $new_customer_id = $prestashop_conn->insert_id;
                echo "Customer ID $customer_id inserted successfully as new customer ID $new_customer_id.<br>";

                // Insert address for the new customer
                $insert_address_sql = "INSERT INTO ps_address (id_customer, address1, postcode, city, id_country, date_add, date_upd) VALUES ('$new_customer_id', '$street_address', '$postcode', '$city', '$country_id', NOW(), NOW())";
                if ($prestashop_conn->query($insert_address_sql) === TRUE) {
                    echo "Address for customer ID $new_customer_id inserted successfully.<br>";
                } else {
                    echo "Error inserting address for customer ID $new_customer_id: " . $prestashop_conn->error . "<br>";
                }
            } else {
                echo "Error inserting customer ID $customer_id: " . $prestashop_conn->error . "<br>";
            }
        }
    }
} else {
    echo "No customers found in osCommerce database.";
}

// Close database connections
$oscommerce_conn->close();
$prestashop_conn->close();

?>
