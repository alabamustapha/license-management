<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Edit Client";
$page_message="Edit client to use licensed products. Modify client details, and click the 'Submit' button (deleting record will also delete all licenses for this product).<br><br>Client's email address will be used to automatically verify his license.";
$page_message_class="alert alert-info";
$page_header_file_no_data="clients_view.php";


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


if (!filter_var($client_id, FILTER_VALIDATE_INT))
    {
    header("Location: $page_header_file_no_data");
    exit();
    }


if (!isset($submit_ok)) //get record details only if form wasn't submitted (otherwise data entered by user will be overwritten with data from database in case of form submission failure)
    {
    $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_clients WHERE client_id=?");
    if ($stmt)
        {
        mysqli_stmt_bind_param($stmt, "i", $client_id);
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
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_clients WHERE client_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_callbacks WHERE client_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            //$affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_installations WHERE client_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            //$affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_licenses WHERE client_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            //$affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_reports WHERE account_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            //$affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        if ($removed_records>0)
            {
            $page_message="Deleted $removed_records client(s) from the database.";
            createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
            header("Location: $page_header_file_no_data");
            exit();
            }
        }

    if (!empty($client_fname) && !empty($client_lname) && filter_var($client_email, FILTER_VALIDATE_EMAIL) && validateNumberOrRange($client_status, 0, 2))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT client_cancel_date FROM apl_clients WHERE client_id=?"); //get old client_cancel_date, so this value can be used when client was deactivated previously and his status wasn't changed now
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $client_id);
            $exec=mysqli_stmt_execute($stmt);
            $results=mysqli_stmt_get_result($stmt);
            $results_total=mysqli_num_rows($results);
            mysqli_stmt_close($stmt);
            }

        if ($results_total>0)
            {
            while ($row=mysqli_fetch_assoc($results))
                {
                foreach ($row as $key=>$value)
                    {
                    $$key=$value;
                    }
                }
            }

        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_clients SET client_fname=?, client_lname=?, client_email=?, client_cancel_date=?, client_status=? WHERE client_id=?");
            if ($stmt)
                {
                if ($client_status==1) {$client_cancel_date="0000-00-00";} else {if (empty($client_cancel_date) || !verifyDate($client_cancel_date, "Y-m-d")) {$client_cancel_date=date("Y-m-d");}}

                mysqli_stmt_bind_param($stmt, "ssssii", $client_fname, $client_lname, $client_email, $client_cancel_date, $client_status, $client_id);
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
        $page_message="Client $client_fname $client_lname ($client_email) updated.";
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
