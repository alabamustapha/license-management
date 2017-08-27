<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


if ($is_logged_in_admin==1) //redirect to admin area if logged in
    {
    header("Location: index.php");
    exit();
    }


$page_title="Reset Password";
$page_message="Enter new password.";
$page_message_class="alert alert-info";


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


if (empty($admin_reset_login))
    {
    header("Location: login.php");
    exit();
    }


if (isset($submit_ok))
    {
    if (!empty($admin_reset_login) && !empty($admin_password_login) && !empty($admin_password_login2) && $admin_password_login==$admin_password_login2 && strlen($admin_password_login)>5 && !empty($admin_reset_login))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_admins WHERE admin_reset=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "s", $admin_reset_login);
            $exec=mysqli_stmt_execute($stmt);
            $check_admin_reset=mysqli_stmt_get_result($stmt);
            $check_admin_reset_total=mysqli_num_rows($check_admin_reset);
            mysqli_stmt_close($stmt);
            }

        if ($check_admin_reset_total>0) //admin exists, reset password
            {
            while ($check_admin_reset_row=mysqli_fetch_assoc($check_admin_reset))
                {
                foreach ($check_admin_reset_row as $check_admin_reset_key=>$check_admin_reset_value)
                    {
                    $$check_admin_reset_key=$check_admin_reset_value;
                    }

                $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_admins SET admin_password=?, admin_reset=? WHERE admin_id=?");
                if ($stmt)
                    {
                    $admin_password=password_hash($admin_password_login, PASSWORD_DEFAULT);
                    $admin_reset="";

                    mysqli_stmt_bind_param($stmt, "ssi", $admin_password, $admin_reset, $admin_id);
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
        else //admin doesn't exist
            {
            $error_detected=1;
            $error_details.="Invalid security key.<br>";
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="Passwords are too short or don't match (or security key is invalid).<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Password for $admin_email successfully reset.";
        createReport(strip_tags($page_message), 0, 1, 0); //creates extended report
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
