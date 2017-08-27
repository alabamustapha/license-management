<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");


if (isset($_SERVER['REMOTE_ADDR'])) {$ip_address=$_SERVER['REMOTE_ADDR'];}
if (isset($_SERVER['HTTP_REFERER'])) {$refer=$_SERVER['HTTP_REFERER'];}
if (isset($_SERVER['REQUEST_URI'])) {$requested_page=$_SERVER['REQUEST_URI'];}
if (isset($_SERVER['SCRIPT_FILENAME'])) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);}
if (isset($_SERVER['HTTP_USER_AGENT'])) {$user_agent=$_SERVER['HTTP_USER_AGENT'];}


$action_success=0; //will be changed to 1 later only if everything OK
$error_detected=0; //will be changed to 1 later if error occurs
$error_details=""; //will be filled with errors (if any)
$added_records=0;
$updated_records=0;
$removed_records=0;


$api_action_success=0;
$api_error_detected=0;
$api_error_details="";
$admin_id=null; //used for compatibility with createReport function in the same file in /apl_admin directory. since admin is not logged in when API is called, $admin_id must be null


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


if (!filter_var($client_id, FILTER_VALIDATE_INT)) //THIS CODE SNIPPET IS CUSTOM IN API. ORIGINAL CODE HASN'T THIS SNIPPET (ID IS VALIDATED EARLIER IN ORIGINAL CODE)
    {
    echo "Invalid account ID";
    exit();
    }


if (!empty($api_key_secret) && !empty($api_function) && $api_post_key==hash("sha256", $ROOT_URL) && $submit_ok=="Submit" && $refer=="$ROOT_URL/apl_api/api.php" && in_array($user_agent, $SUPPORTED_BROWSERS_ARRAY)) //prevent someone from posting to this file directly
    {
    if ($API_STATUS==1 && in_array($api_function, $SUPPORTED_API_FUNCTIONS_ARRAY))
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_api_keys WHERE api_key_secret=? AND api_key_status='1'");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "s", $api_key_secret);
            $exec=mysqli_stmt_execute($stmt);
            $api_key_results=mysqli_stmt_get_result($stmt);
            $api_key_results_total=mysqli_num_rows($api_key_results);
            mysqli_stmt_close($stmt);
            }

        if ($api_key_results_total<1)
            {
            $api_error_detected=1;
            $api_error_details.="Invalid or inactive API key.<br>";
            }
        else
            {
            while ($api_key_row=mysqli_fetch_assoc($api_key_results))
                {
                foreach ($api_key_row as $api_key_key=>$api_key_value)
                    {
                    $$api_key_key=$api_key_value;
                    }
                }

            if (!empty($api_key_ip))
                {
                $api_key_ip_array=explode(",", $api_key_ip);
                if (!in_array($ip_address, $api_key_ip_array))
                    {
                    $api_error_detected=1;
                    $api_error_details.="Invalid IP address.<br>";
                    }
                }

            $api_permissions_name="api_key_".$api_function; //since each permission in database starts with api_key_ prefix, add this prefix to name of function submitted by user for quick permissions check
            if ($$api_permissions_name!=1)
                {
                $api_error_detected=1;
                $api_error_details.="Invalid API key permissions.<br>";
                }

            if ($api_error_detected!=1)
                {
                $api_action_success=1;
                }
            }
        }
    else
        {
        $api_error_detected=1;
        $api_error_details.="API not enabled or invalid API function.<br>";
        }

    if ($api_action_success==1) //everything OK
        {
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
                    echo $page_message; //THIS LINE IS CUSTOM IN API. ORIGINAL CODE CONTAINS header("Location: $page_header_file_no_data"); LINE
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
        }
    else //display error message
        {
        $page_message="The action could not be completed because of this error:<br><br>$api_error_details";
        }


    echo $page_message;
    }
