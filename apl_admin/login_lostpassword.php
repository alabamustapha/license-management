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


$page_title="Lost Password";
$page_message="Enter your email address.";
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


if (isset($submit_ok))
    {
    if (filter_var($admin_email_login, FILTER_VALIDATE_EMAIL))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_admins WHERE admin_email=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "s", $admin_email_login);
            $exec=mysqli_stmt_execute($stmt);
            $check_admin_lostpassword=mysqli_stmt_get_result($stmt);
            $check_admin_lostpassword_total=mysqli_num_rows($check_admin_lostpassword);
            mysqli_stmt_close($stmt);
            }

        if ($check_admin_lostpassword_total>0) //admin exists, send reset email
            {
            while ($check_admin_lostpassword_row=mysqli_fetch_assoc($check_admin_lostpassword))
                {
                foreach ($check_admin_lostpassword_row as $check_admin_lostpassword_key=>$check_admin_lostpassword_value)
                    {
                    $$check_admin_lostpassword_key=$check_admin_lostpassword_value;
                    }

                $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_admins SET admin_reset=? WHERE admin_id=?");
                if ($stmt)
                    {
                    $admin_reset=hash("sha256", microtime().$admin_email);

                    mysqli_stmt_bind_param($stmt, "si", $admin_reset, $admin_id);
                    $exec=mysqli_stmt_execute($stmt);
                    $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                    mysqli_stmt_close($stmt);
                    }

                if ($updated_records>0)
                    {
                    emailAdmin("Password Recovery", "Someone at <a href='$ROOT_URL/apl_admin/'>$ROOT_URL/apl_admin/</a> requested admin password to be reset. If you lost your password, <a href='$ROOT_URL/apl_admin/login_resetpassword.php?admin_reset_login=$admin_reset'>click this link</a> to reset it. If for some reasons the link doesn't work, copy/paste it manually into your browser - $ROOT_URL/apl_admin/login_resetpassword.php?admin_reset_login=$admin_reset<br><br><b>Attention:</b>If you have never requested admin password to be reset, simply ignore and delete this email - your account is 100% secure.");
                    $action_success=1;
                    }
                }
            }
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Password reset instructions sent to $admin_email.";
        createReport(strip_tags($page_message), 0, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="Non-existing user or invalid email address.";
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
