<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


if ($is_logged_in_admin==1) //redirect to the dashboard if logged in
    {
    header("Location: index.php");
    exit();
    }


$page_title="Administrator Login";
$page_message="Login to start your session.";
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
    if (!empty($admin_email_login) && !empty($admin_password_login))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_admins WHERE admin_email=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "s", $admin_email_login);
            $exec=mysqli_stmt_execute($stmt);
            $check_admin_login=mysqli_stmt_get_result($stmt);
            $check_admin_login_total=mysqli_num_rows($check_admin_login);
            mysqli_stmt_close($stmt);
            }

        if ($check_admin_login_total>0) //admin exists, check password
            {
            while ($check_admin_login_row=mysqli_fetch_assoc($check_admin_login))
                {
                foreach ($check_admin_login_row as $check_admin_login_key=>$check_admin_login_value)
                    {
                    $$check_admin_login_key=$check_admin_login_value;
                    }

                if (password_verify($admin_password_login, $admin_password)) //everything ok
                    {
                    $cookie_expiration_time=0; //cookie will expire after browser is closed
                    if (isset($remember_me) && $remember_me==1) {$cookie_expiration_time=strtotime("+1 month");} //store cookie for 1 month
                    $action_success=1;

                    setcookie($COOKIE_PREFIX."_admin_id", $admin_id, $cookie_expiration_time, null, null, null, true); //id
                    setcookie($COOKIE_PREFIX."_admin_email", password_hash($admin_email, PASSWORD_DEFAULT), $cookie_expiration_time, null, null, null, true); //email (encrypted with password_hash)
                    setcookie($COOKIE_PREFIX."_admin_key", password_hash($admin_id.$admin_email.$admin_password, PASSWORD_DEFAULT), $cookie_expiration_time, null, null, null, true); //key (id, email and password hash) (encrypted with password_hash)
                    setcookie($COOKIE_PREFIX."_admin_data_authenticity", password_hash($ip_address.$user_agent, PASSWORD_DEFAULT), $cookie_expiration_time, null, null, null, true); //user agent and IP (encrypted with password_hash)

                    if (!empty($admin_reset)) //reset admin_reset value (in case lost password email was sent previously, but admin didn't change his password)
                        {
                        $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_admins SET admin_reset=? WHERE admin_id=?");
                        if ($stmt)
                            {
                            $admin_reset="";

                            mysqli_stmt_bind_param($stmt, "si", $admin_reset, $admin_id);
                            $exec=mysqli_stmt_execute($stmt);
                            $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                            mysqli_stmt_close($stmt);
                            }
                        }

                    if (isset($_COOKIE[$COOKIE_PREFIX."_requested_page"])) //cookie with requested page set, unset cookie and redirect to this page
                        {
                        $requested_page_cookie=$_COOKIE[$COOKIE_PREFIX."_requested_page"];
                        setcookie($_COOKIE[$COOKIE_PREFIX."_requested_page"], false, 1);
                        header("Location: $requested_page_cookie");
                        exit();
                        }
                    else //redirect to the dashboard
                        {
                        header("Location: index.php");
                        exit();
                        }
                    }
                }
            }
        }

    if ($action_success!=1) //action failed
        {
        $page_message="Non-existing user or invalid login credentials.";
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
