<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Database Cleanup Settings";
$page_message="Configure database cleanup settings. Automatic removal of old data can slightly reduce database usage and improve overall performance.";
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
    if (validateNumberOrRange($database_cleanup_callbacks, 0, 1) && validateNumberOrRange($database_cleanup_reports, 0, 1) && validateNumberOrRange($database_cleanup_licenses, 0, 1) && validateNumberOrRange($database_cleanup_days, 0, 365))
        {
        if ($error_detected!=1)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_settings SET DATABASE_CLEANUP_CALLBACKS=?, DATABASE_CLEANUP_REPORTS=?, DATABASE_CLEANUP_LICENSES=?, DATABASE_CLEANUP_DAYS=?");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "iiii", $database_cleanup_callbacks, $database_cleanup_reports, $database_cleanup_licenses, $database_cleanup_days);
                $exec=mysqli_stmt_execute($stmt);
                $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                mysqli_stmt_close($stmt);
                }

            if ($updated_records<1) //no records affected
                {
                $error_detected=1;
                $error_details.="Invalid settings or duplicated record (no new data).<br>";
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
        $error_details.="Invalid time range.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Database cleanup settings updated.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The database could not be updated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }


    //get all settings again
    $settings_results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_settings");
    while ($settings_row=mysqli_fetch_assoc($settings_results))
        {
        foreach ($settings_row as $settings_key=>$settings_value)
            {
            $$settings_key=$settings_value;
            }
        }
    }


$database_cleanup_days_array=returnNumbersDropdownArray(array(0, 1, 7, 14, 30, 60, 90, 180, 365), "Days", "Disabled", $DATABASE_CLEANUP_DAYS);


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
