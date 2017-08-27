<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Add New Client";
$page_message="Add new client to use licensed products. Enter first and last name, email address, and click the 'Submit' button.<br><br>Client's email address will be used to automatically verify his license.";
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


if (isset($submit_ok)) //code between {} tags is identical in files with the same name in /apl_admin and /apl_api directories
    {
    if (!empty($client_fname) && !empty($client_lname) && filter_var($client_email, FILTER_VALIDATE_EMAIL) && validateNumberOrRange($client_status, 0, 2))
        {
        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "INSERT IGNORE INTO apl_clients (client_fname, client_lname, client_email, client_active_date, client_cancel_date, client_status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt)
                {
                $client_active_date=date("Y-m-d");
                if ($client_status==1) {$client_cancel_date="0000-00-00";} else {$client_cancel_date=date("Y-m-d");}

                mysqli_stmt_bind_param($stmt, "sssssi", $client_fname, $client_lname, $client_email, $client_active_date, $client_cancel_date, $client_status);
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
        $error_details.="Invalid first name, last name, email address, or status.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Client $client_fname $client_lname ($client_email) added to the database.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The database could not be updated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }
    }


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
