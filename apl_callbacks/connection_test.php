<?php
//used to check if script can connect to Auto PHP Licenser installation
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");


//get IP, refer, requested page, script filename, and user agent (browser)
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


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


//check basic data
if (filter_var($ip_address, FILTER_VALIDATE_IP) && in_array($user_agent, $SUPPORTED_BROWSERS_ARRAY) && $connection_hash==hash("sha256", "connection_test"))
    {
    echo "<connection_test>OK</connection_test>";
    }
