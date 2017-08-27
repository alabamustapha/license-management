<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="View License Reports";
$page_message="View license reports. If any report needs to be deleted, check the box near report text and click the 'Submit' button.";
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


//list license reports
function returnLicenseReportsArray()
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_reports LEFT JOIN apl_clients ON apl_reports.account_id=apl_clients.client_id WHERE apl_reports.report_system='0' ORDER BY report_id DESC");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['report_status_formatted']=returnFormattedReportStatusArray($item_array['report_failure'], "Success", "Failure");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


if (isset($submit_ok))
    {
    if (isset($report_ids_array) && is_array($report_ids_array)) //delete selected records
        {
        foreach ($report_ids_array as $report_id)
            {
            if (filter_var($report_id, FILTER_VALIDATE_INT))
                {
                $stmt=mysqli_prepare($GLOBALS["mysqli"], "DELETE FROM apl_reports WHERE report_id=?");
                if ($stmt)
                    {
                    mysqli_stmt_bind_param($stmt, "i", $report_id);
                    $exec=mysqli_stmt_execute($stmt);
                    $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$removed_records=$removed_records+$affected_rows;}
                    mysqli_stmt_close($stmt);
                    }
                }
            }

        if ($removed_records<1) //no records affected
            {
            $error_detected=1;
            $error_details.="No record or invalid record selected.<br>";
            }
        else //records affected
            {
            $action_success=1;
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="No record or invalid record selected.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Deleted $removed_records license report(s) from the database.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The database could not be updated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }
    }


$license_reports_array=returnLicenseReportsArray();


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
