<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Add New License";
$page_message="Add new license to be used. It's possible to add licenses with and without client's profile.<br><br>With client's profile (when client's name and email address are known): select client and product from the list, and click the 'Submit' button. Client will need to enter his email address during script installation to verify his license.<br><br>Without client's profile (when no client's information is known or an anonymous license needs to be issued): select product from the list and enter unique license code. Client will need to enter this code during script installation to verify his license.<br><br>If IP address and/or domain is set, product will only work on specified IP and/or domain. If licensed domain is entered as clientdomain.com, product will work on clientdomain.com and clientdomain.com/any/directory. If licensed domain is entered as clientdomain.com/path, product will only work on clientdomain.com/path. It's possible to add multiple licensed IPs and/or domains by separating them with comma (,) symbol. If installations limit is set, client will not be able to run more copies of licensed product than specified number.<br><br>If expiration date is set (it can't be earlier than today's date), application will stop working after this date (license expiration date can be renewed later).";
$page_message_class="alert alert-info";


$action_success=0; //will be changed to 1 later only if everything OK
$error_detected=0; //will be changed to 1 later if error occurs
$error_details=""; //will be filled with errors (if any)
$added_records=0;
$updated_records=0;
$removed_records=0;


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


//return products dropdown
function returnProductsDropdownArray($product_id)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_products ORDER BY product_title");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['value']=$item_array['product_id'];
        $item_array['title']=$item_array['product_title'];

        if ($item_array['product_id']==$product_id)
            {
            $item_array['selected']=" selected";
            }
        else
            {
            $item_array['selected']="";
            }

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return clients dropdown
function returnClientsDropdownArray($client_id)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_clients ORDER BY client_fname, client_lname");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['value']=$item_array['client_id'];
        $item_array['title']="$item_array[client_fname] $item_array[client_lname] ($item_array[client_email])";

        if ($item_array['client_id']==$client_id)
            {
            $item_array['selected']=" selected";
            }
        else
            {
            $item_array['selected']="";
            }

        $root_array[]=$item_array;
        }

    return $root_array;
    }


if (isset($submit_ok)) //code between {} tags is identical in files with the same name in /apl_admin and /apl_api directories
    {
    if (filter_var($product_id, FILTER_VALIDATE_INT) && validateNumberOrRange($license_require_domain, 0, 1) && validateNumberOrRange($license_status, 0, 2))
        {
        if (!filter_var($client_id, FILTER_VALIDATE_INT) && empty($license_code))
            {
            $error_detected=1;
            $error_details.="Invalid client or license code.<br>";
            }

        if (filter_var($client_id, FILTER_VALIDATE_INT) && !empty($license_code))
            {
            $error_detected=1;
            $error_details.="Invalid license type (license can only be either personal (client-based) or anonymous (code-based)).<br>";
            }

        if (!empty($license_ip))
            {
            $license_ip_array=explode(",", $license_ip);
            foreach ($license_ip_array as $license_ip_array_key=>$license_ip_array_value)
                {
                if (!filter_var($license_ip_array_value, FILTER_VALIDATE_IP))
                    {
                    $error_detected=1;
                    $error_details.="Invalid IP address.<br>";
                    break;
                    }
                }
            }

        if (!empty($license_domain))
            {
            $license_domain_array=explode(",", $license_domain);
            foreach ($license_domain_array as $license_domain_array_key=>$license_domain_array_value)
                {
                if (!validateRawDomain(getRawDomain($license_domain_array_value)) || filter_var($license_domain_array_value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) || !ctype_alnum(substr($license_domain_array_value, -1))) //invalid TLD, scheme included, or last symbol is not alphanumeric (most likely ends with / or another non-alphanumeric character)
                    {
                    $error_detected=1;
                    $error_details.="Invalid domain.<br>";
                    break;
                    }
                }
            }

        if (!empty($license_limit) && !filter_var($license_limit, FILTER_VALIDATE_INT))
            {
            $error_detected=1;
            $error_details.="Invalid installations limit.<br>";
            }

        if (!empty($license_expire_date) && !verifyDate($license_expire_date, "Y-m-d"))
            {
            $error_detected=1;
            $error_details.="Invalid license expiration date.<br>";
            }

        if (!empty($license_updates_date) && !verifyDate($license_updates_date, "Y-m-d"))
            {
            $error_detected=1;
            $error_details.="Invalid updates expiration date.<br>";
            }

        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "INSERT IGNORE INTO apl_licenses (client_id, license_code, product_id, license_order_number, license_ip, license_domain, license_require_domain, license_limit, license_active_date, license_cancel_date, license_expire_date, license_updates_date, license_comments, license_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt)
                {
                if (empty($client_id) || !filter_var($client_id, FILTER_VALIDATE_INT)) {$client_id=null;}
                if (empty($license_code)) {$license_code=null;}
                $license_active_date=date("Y-m-d");
                if ($license_status==1) {$license_cancel_date="0000-00-00";} else {if (empty($license_cancel_date) || !verifyDate($license_cancel_date, "Y-m-d")) {$license_cancel_date=date("Y-m-d");}} //set cancel date to now only if license is inactive and no previous cancel date set
                if (empty($license_expire_date) || !verifyDate($license_expire_date, "Y-m-d")) {$license_expire_date="0000-00-00";}
                if (empty($license_updates_date) || !verifyDate($license_updates_date, "Y-m-d")) {$license_updates_date="0000-00-00";}

                mysqli_stmt_bind_param($stmt, "isisssiisssssi", $client_id, $license_code, $product_id, $license_order_number, $license_ip, $license_domain, $license_require_domain, $license_limit, $license_active_date, $license_cancel_date, $license_expire_date, $license_updates_date, $license_comments, $license_status);
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

                $license_id=mysqli_insert_id($GLOBALS["mysqli"]);
                $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_licenses LEFT JOIN apl_products ON apl_licenses.product_id=apl_products.product_id LEFT JOIN apl_clients ON apl_clients.client_id=apl_licenses.client_id WHERE apl_licenses.license_id=?"); //fetch product and client details to be used in reports
                if ($stmt)
                    {
                    mysqli_stmt_bind_param($stmt, "i", $license_id);
                    $exec=mysqli_stmt_execute($stmt);
                    $license_results=mysqli_stmt_get_result($stmt);
                    $license_results_total=mysqli_num_rows($license_results);
                    mysqli_stmt_close($stmt);
                    }

                if ($license_results_total>0) //fetch details
                    {
                    while ($license_results_row=mysqli_fetch_assoc($license_results))
                        {
                        foreach ($license_results_row as $license_results_key=>$license_results_value)
                            {
                            $$license_results_key=$license_results_value;
                            }
                        }
                    }
                }
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="Invalid product or license status.<br>";
        }

    if ($action_success==1) //everything OK
        {
        if (filter_var($client_id, FILTER_VALIDATE_INT)) {$client_formatted="$client_fname $client_lname ($client_email)";}
        else {$client_formatted=$license_code;}

        $page_message="$product_title license for $client_formatted added to the database.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The database could not be updated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }
    }


//set default values to be used when no values of essential variables are set or values need to be reset
if (empty($product_id) || !filter_var($product_id, FILTER_VALIDATE_INT) || $action_success==1) {$product_id=0;}
if (empty($client_id) || !filter_var($client_id, FILTER_VALIDATE_INT) || $action_success==1) {$client_id=0;}


$products_array=returnProductsDropdownArray($product_id);
$clients_array=returnClientsDropdownArray($client_id);


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
