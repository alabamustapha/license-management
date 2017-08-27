<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Administrator Profile";
$page_message="Edit administrator profile. Modify account details and security options, and click the 'Submit' button.";
$page_message_class="alert alert-info";
$page_header_file_no_data="logout.php";


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
    if (filter_var($admin_email_new_1, FILTER_VALIDATE_EMAIL) && filter_var($admin_email_new_2, FILTER_VALIDATE_EMAIL) || !empty($admin_password_new_1) && !empty($admin_password_new_2) || validateNumberOrRange($admin_data_authenticity_new, 0, 1))
        {
        if (!empty($admin_email_new_1) || !empty($admin_email_new_2))
            {
            if (!filter_var($admin_email_new_1, FILTER_VALIDATE_EMAIL) || !filter_var($admin_email_new_2, FILTER_VALIDATE_EMAIL))
                {
                $error_detected=1;
                $error_details.="Email addresses are invalid.<br>";
                }

            if ($admin_email_new_1!=$admin_email_new_2)
                {
                $error_detected=1;
                $error_details.="Email addresses do not match.<br>";
                }
            }

        if (!empty($admin_password_new_1) || !empty($admin_password_new_2))
            {
            if ($admin_password_new_1!=$admin_password_new_2)
                {
                $error_detected=1;
                $error_details.="Passwords do not match.<br>";
                }

            if (strlen($admin_password_new_1)<5 || strlen($admin_password_new_2)<5)
                {
                $error_detected=1;
                $error_details.="Passwords are too short.<br>";
                }
            }

        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_admins SET admin_email=?, admin_password=?, admin_data_authenticity=? WHERE admin_id=?");
            if ($stmt)
                {
                if (!empty($admin_email_new_1)) {$admin_email=$admin_email_new_1;} //valid email was entered, overwrite old value. otherwise, old admin_email from login_check.php will be used
                if (!empty($admin_password_new_1)) {$admin_password=password_hash($admin_password_new_1, PASSWORD_DEFAULT);} //valid password was entered, overwrite old value. otherwise, old admin_password from login_check.php will be used
                $admin_data_authenticity=$admin_data_authenticity_new; //only valid authenticity can be selected, overwrite old value

                mysqli_stmt_bind_param($stmt, "ssii", $admin_email, $admin_password, $admin_data_authenticity, $admin_id);
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
        $error_details.="Invalid email, password, or protection status.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Administrator profile updated.";
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
if (empty($admin_email_new_1) || !filter_var($admin_email_new_1, FILTER_VALIDATE_EMAIL)) {$admin_email_new_1=$admin_email;}
if (empty($admin_email_new_2) || !filter_var($admin_email_new_2, FILTER_VALIDATE_EMAIL)) {$admin_email_new_2=$admin_email;}
if (empty($admin_data_authenticity_new) || !validateNumberOrRange($admin_data_authenticity_new, 0, 1)) {$admin_data_authenticity_new=$admin_data_authenticity;}


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
