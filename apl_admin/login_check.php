<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");


//get IP, refer, requested page, script filename, and user agent (browser)
if (isset($_SERVER['REMOTE_ADDR'])) {$ip_address=$_SERVER['REMOTE_ADDR'];}
if (isset($_SERVER['HTTP_REFERER'])) {$refer=$_SERVER['HTTP_REFERER'];}
if (isset($_SERVER['REQUEST_URI'])) {$requested_page=$_SERVER['REQUEST_URI'];}
if (isset($_SERVER['SCRIPT_FILENAME'])) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);}
if (isset($_SERVER['HTTP_USER_AGENT'])) {$user_agent=$_SERVER['HTTP_USER_AGENT'];}


$is_logged_in_admin=0; //will be changed to 1 after successful login


if (isset($_COOKIE[$COOKIE_PREFIX."_admin_id"]) && isset($_COOKIE[$COOKIE_PREFIX."_admin_email"]) && isset($_COOKIE[$COOKIE_PREFIX."_admin_key"]) && isset($_COOKIE[$COOKIE_PREFIX."_admin_data_authenticity"])) //login cookie set
    {
    $admin_id_cookie=$_COOKIE[$COOKIE_PREFIX."_admin_id"]; //id
    $admin_email_cookie=$_COOKIE[$COOKIE_PREFIX."_admin_email"]; //email (encrypted with password_hash)
    $admin_key_cookie=$_COOKIE[$COOKIE_PREFIX."_admin_key"]; //key (id, email and password hash) (encrypted with password_hash)
    $admin_data_authenticity_cookie=$_COOKIE[$COOKIE_PREFIX."_admin_data_authenticity"]; //user agent and IP (encrypted with password_hash)

    if (filter_var($admin_id_cookie, FILTER_VALIDATE_INT) && !empty($admin_email_cookie) && !empty($admin_key_cookie)) //authentication data exists
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_admins WHERE admin_id=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "i", $admin_id_cookie);
            $exec=mysqli_stmt_execute($stmt);
            $check_admin_login=mysqli_stmt_get_result($stmt);
            $check_admin_login_total=mysqli_num_rows($check_admin_login);
            mysqli_stmt_close($stmt);
            }

        if ($check_admin_login_total>0)
            {
            while ($check_admin_login_row=mysqli_fetch_assoc($check_admin_login))
                {
                foreach ($check_admin_login_row as $check_admin_key=>$check_admin_value)
                    {
                    $$check_admin_key=$check_admin_value;
                    }

                if ($admin_id==$admin_id_cookie && password_verify($admin_email, $admin_email_cookie) && password_verify($admin_id.$admin_email.$admin_password, $admin_key_cookie)) //everything ok
                    {
                    $is_logged_in_admin=1;
                    }

                if ($admin_data_authenticity==1 && !password_verify($ip_address.$user_agent, $admin_data_authenticity_cookie)) //additional data authentication enabled, perform additional checks
                    {
                    $is_logged_in_admin=0;
                    }
                }
            }
        }
    }


if ($is_logged_in_admin!=1 && !in_array($script_filename, array("login.php", "login_lostpassword.php", "login_resetpassword.php"))) //login check failed and requested page is protected, redirect to login form
    {
    if (empty($requested_page)) {$requested_page="index.php";}
    setcookie($COOKIE_PREFIX."_requested_page", $requested_page, 0, null, null, null, true); //cookie with requested page, valid until browser is closed
    header("Location: login.php");
    exit();
    }
