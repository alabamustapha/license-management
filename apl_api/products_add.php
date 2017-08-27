<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");


if (isset($_SERVER['REMOTE_ADDR'])) {$ip_address=$_SERVER['REMOTE_ADDR'];}
if (isset($_SERVER['HTTP_REFERER'])) {$refer=$_SERVER['HTTP_REFERER'];}
if (isset($_SERVER['REQUEST_URI'])) {$requested_page=$_SERVER['REQUEST_URI'];}
if (isset($_SERVER['SCRIPT_FILENAME'])) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);}
if (isset($_SERVER['HTTP_USER_AGENT'])) {$user_agent=$_SERVER['HTTP_USER_AGENT'];}


$action_success=0; //will be changed to 1 later only if everything OK
$error_detected=0; //will be changed to 1 later if error occurs
$error_details=""; //will be filled with errors (if any)
$added_records=0;
$updated_records=0;
$removed_records=0;


$api_action_success=0;
$api_error_detected=0;
$api_error_details="";
$admin_id=null; //used for compatibility with createReport function in the same file in /apl_admin directory. since admin is not logged in when API is called, $admin_id must be null


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


if (!empty($api_key_secret) && !empty($api_function) && $api_post_key==hash("sha256", $ROOT_URL) && $submit_ok=="Submit" && $refer=="$ROOT_URL/apl_api/api.php" && in_array($user_agent, $SUPPORTED_BROWSERS_ARRAY)) //prevent someone from posting to this file directly
    {
    if ($API_STATUS==1 && in_array($api_function, $SUPPORTED_API_FUNCTIONS_ARRAY))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_api_keys WHERE api_key_secret=? AND api_key_status='1'");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "s", $api_key_secret);
            $exec=mysqli_stmt_execute($stmt);
            $api_key_results=mysqli_stmt_get_result($stmt);
            $api_key_results_total=mysqli_num_rows($api_key_results);
            mysqli_stmt_close($stmt);
            }

        if ($api_key_results_total<1)
            {
            $api_error_detected=1;
            $api_error_details.="Invalid or inactive API key.<br>";
            }
        else
            {
            while ($api_key_row=mysqli_fetch_assoc($api_key_results))
                {
                foreach ($api_key_row as $api_key_key=>$api_key_value)
                    {
                    $$api_key_key=$api_key_value;
                    }
                }

            if (!empty($api_key_ip))
                {
                $api_key_ip_array=explode(",", $api_key_ip);
                if (!in_array($ip_address, $api_key_ip_array))
                    {
                    $api_error_detected=1;
                    $api_error_details.="Invalid IP address.<br>";
                    }
                }

            $api_permissions_name="api_key_".$api_function; //since each permission in database starts with api_key_ prefix, add this prefix to name of function submitted by user for quick permissions check
            if ($$api_permissions_name!=1)
                {
                $api_error_detected=1;
                $api_error_details.="Invalid API key permissions.<br>";
                }

            if ($api_error_detected!=1)
                {
                $api_action_success=1;
                }
            }
        }
    else
        {
        $api_error_detected=1;
        $api_error_details.="API not enabled or invalid API function.<br>";
        }

    if ($api_action_success==1) //everything OK
        {
        if (isset($submit_ok)) //code between {} tags is identical in files with the same name in /apl_admin and /apl_api directories
            {
            if (!empty($product_title) && !empty($product_sku) && validateNumberOrRange($product_status, 0, 2))
                {
                if (!empty($product_url_homepage) && !filter_var($product_url_homepage, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED))
                    {
                    $error_detected=1;
                    $error_details.="Invalid product homepage URL.<br>";
                    }

                if ($error_detected!=1)
                    {
                    $stmt=mysqli_prepare($GLOBALS["mysqli"], "INSERT IGNORE INTO apl_products (product_title, product_description, product_sku, product_url_homepage, product_url_download, product_date, product_version, product_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt)
                        {
                        $product_date=date("Y-m-d");

                        mysqli_stmt_bind_param($stmt, "sssssssi", $product_title, $product_description, $product_sku, $product_url_homepage, $product_url_download, $product_date, $product_version, $product_status);
                        $exec=mysqli_stmt_execute($stmt);
                        $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$added_records=$added_records+$affected_rows;}
                        mysqli_stmt_close($stmt);
                        }

                    if ($added_records<1) //no records affected
                        {
                        $error_detected=1;
                        $error_details.="Invalid record details or duplicated record (no new data).<br>";
                        }
                    else //records affected
                        {
                        $action_success=1;
                        }
                    }
                }
            else
                {
                $error_detected=1;
                $error_details.="Invalid product name, SKU, or status.<br>";
                }

            if ($action_success==1) //everything OK
                {
                $page_message="Product $product_title added to the database.";
                createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
                $page_message_class="alert alert-success";
                }
            else //display error message
                {
                $page_message="The database could not be updated because of this error:<br><br>$error_details";
                $page_message_class="alert alert-danger";
                }
            }
        }
    else //display error message
        {
        $page_message="The action could not be completed because of this error:<br><br>$api_error_details";
        }


    echo $page_message;
    }
