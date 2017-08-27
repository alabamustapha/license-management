<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Customize Notifications";
$page_message="Configure custom messages to be displayed during license check. All variables surrounded by % sign will be converted into real values during license check.<br><br>For more information on supported variables, refer to the Help section.";
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


if (!isset($submit_ok)) //get record details only if form wasn't submitted (otherwise data entered by user will be overwritten with data from database in case of form submission failure)
    {
    $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_notifications WHERE notification_id=?");
    if ($stmt)
        {
        $notification_id=1;

        mysqli_stmt_bind_param($stmt, "i", $notification_id);
        $exec=mysqli_stmt_execute($stmt);
        $results=mysqli_stmt_get_result($stmt);
        $results_total=mysqli_num_rows($results);
        mysqli_stmt_close($stmt);
        }

    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $$key=$value;
            }
        }
    }


if (isset($submit_ok))
    {
    if (!empty($notification_license_ok) && !empty($notification_license_not_found) && !empty($notification_invalid_ip) && !empty($notification_invalid_domain) && !empty($notification_domain_required) && !empty($notification_domain_in_use) && !empty($notification_license_suspended) && !empty($notification_license_expired) && !empty($notification_updates_expired) && !empty($notification_license_cancelled) && !empty($notification_license_limit) && !empty($notification_installation_not_found) && !empty($notification_product_inactive) && !empty($notification_invalid_signature) && !empty($notification_unknown_error))
        {
        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_notifications SET notification_license_ok=?, notification_license_not_found=?, notification_invalid_ip=?, notification_invalid_domain=?, notification_domain_required=?, notification_domain_in_use=?, notification_license_suspended=?, notification_license_expired=?, notification_updates_expired=?, notification_license_cancelled=?, notification_license_limit=?, notification_installation_not_found=?, notification_product_inactive=?, notification_invalid_signature=?, notification_unknown_error=?");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "sssssssssssssss", $notification_license_ok, $notification_license_not_found, $notification_invalid_ip, $notification_invalid_domain, $notification_domain_required, $notification_domain_in_use, $notification_license_suspended, $notification_license_expired, $notification_updates_expired, $notification_license_cancelled, $notification_license_limit, $notification_installation_not_found, $notification_product_inactive, $notification_invalid_signature, $notification_unknown_error);
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
        $error_details.="Invalid message(s).<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="License notifications updated.";
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
