<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Edit Installation";
$page_message="Edit software installation. Modify installation details, and click the 'Submit' button. For security reasons, only installation IP address can be modified.";
$page_message_class="alert alert-info";
$page_header_file_no_data="installations_view.php";


$action_success=0; //will be changed to 1 later only if everything OK
$error_detected=0; //will be changed to 1 later if error occurs
$error_details=""; //will be filled with errors (if any)
$added_records=0;
$updated_records=0;
$removed_records=0;


if (isset($_GET)) {$get_values_array=$_GET;} //super variable with all GET variables
if (!empty($get_values_array) && is_array($get_values_array))
    {
    foreach ($get_values_array as $get_values_key=>$get_values_value)
        {
        if (!isset($$get_values_key)) {if (!is_array($get_values_value)) {$$get_values_key=removeInvisibleHtml(rawurldecode($get_values_value));} else {$$get_values_key=array_map("removeInvisibleHtml", $get_values_value);}} //sanitize data (don't overwrite existing variables) //don't overwrite existing variables
        }
    }


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


if (!filter_var($installation_id, FILTER_VALIDATE_INT))
    {
    header("Location: $page_header_file_no_data");
    exit();
    }


//return products disabled dropdown
function returnProductsDropdownDisabledArray($product_id, $action_success)
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
            $item_array['selected']=" disabled";
            }

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return clients disabled dropdown
function returnClientsDropdownDisabledArray($client_id, $action_success)
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
            $item_array['selected']=" disabled";
            }

        $root_array[]=$item_array;
        }

    return $root_array;
    }


if (!isset($submit_ok)) //get record details only if form wasn't submitted (otherwise data entered by user will be overwritten with data from database in case of form submission failure)
    {
    $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE installation_id=?");
    if ($stmt)
        {
        mysqli_stmt_bind_param($stmt, "i", $installation_id);
        $exec=mysqli_stmt_execute($stmt);
        $results=mysqli_stmt_get_result($stmt);
        $results_total=mysqli_num_rows($results);
        mysqli_stmt_close($stmt);
        }

    if ($results_total<1)
        {
        header("Location: $page_header_file_no_data");
        exit();
        }
    else
        {
        while ($row=mysqli_fetch_assoc($results))
            {
            foreach ($row as $key=>$value)
                {
                $$key=$value;
                }
            }
        }
    }


if (isset($submit_ok)) //code between {} tags is identical in files with the same name in /apl_admin and /apl_api directories, EXCEPT header("Location: $page_header_file_no_data"); LINE
    {
    if (isset($delete_record) && $delete_record==1) //delete record
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_installations WHERE installation_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $installation_id);
            $exec=mysqli_stmt_execute($stmt);
            $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        if ($removed_records>0)
            {
            $page_message="Deleted $removed_records installation(s) from the database.";
            createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
            header("Location: $page_header_file_no_data");
            exit();
            }
        }

    if (filter_var($installation_ip, FILTER_VALIDATE_IP))
        {
        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_installations SET installation_ip=? WHERE installation_id=?");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "si", $installation_ip, $installation_id);
                $exec=mysqli_stmt_execute($stmt);
                $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                mysqli_stmt_close($stmt);
                }

            if ($updated_records<1) //no records affected
                {
                $error_detected=1;
                $error_details.="Invalid record details or duplicated record (no new data).<br>";
                }
            else //records affected
                {
                $action_success=1;

                $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations LEFT JOIN apl_products ON apl_installations.product_id=apl_products.product_id LEFT JOIN apl_clients ON apl_clients.client_id=apl_installations.client_id WHERE apl_installations.installation_id=?"); //fetch product and client details to be used in reports
                if ($stmt)
                    {
                    mysqli_stmt_bind_param($stmt, "i", $installation_id);
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
        $error_details.="Invalid IP address.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="$product_title installation on $installation_domain ($installation_ip) updated.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The database could not be updated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }
    }


$products_array=returnProductsDropdownDisabledArray($product_id, $action_success);
$clients_array=returnClientsDropdownDisabledArray($client_id, $action_success);


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
