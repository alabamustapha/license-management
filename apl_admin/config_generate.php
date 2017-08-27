<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Configuration Generator";
$page_message="Automatically generate settings for apl_core_configuration.php file. Select product to be licensed, license verification period, license storage options, and click the 'Submit' button. Once configuration is generated, copy/paste its content to your apl_core_configuration.php file.";
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


//display products dropdown
function returnProductsDropdownArray($product_id)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_products ORDER BY product_title");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['value']=$item_array['product_id'];
        $item_array['title']=$item_array['product_title'];

        if ($item_array['product_id']==$product_id)
            {
            $item_array['selected']=" selected";
            }
        else
            {
            $item_array['selected']="";
            }

        $root_array[]=$item_array;
        }

    return $root_array;
    }


if (isset($submit_ok))
    {
    if (filter_var($product_id, FILTER_VALIDATE_INT) && validateNumberOrRange($config_apl_days, 1, 365) && in_array($config_apl_storage, array("DATABASE", "FILE")) && !empty($config_apl_database_table) && !empty($config_apl_license_file_location) && !empty($config_apl_mysql_file_location))
        {
        if ($error_detected!=1)
            {
            $config_file_content=@file_get_contents("apl_core_configuration_sample.php"); //get example content

            //do replace
            $config_values_bad_array=array('define("APL_SALT", "some_random_text");', 'define("APL_ROOT_URL", "https://www.demo.phpmillion.com/apl");', 'define("APL_PRODUCT_ID", 1);', 'define("APL_DAYS", 7);', 'define("APL_STORAGE", "FILE");', 'define("APL_DATABASE_TABLE", "user_data");', 'define("APL_LICENSE_FILE_LOCATION", "signature/license.key.example");', 'define("APL_MYSQL_FILE_LOCATION", "mysql/mysql.php");', 'define("APL_INCLUDE_KEY_CONFIG", "some_random_text");', 'define("APL_DELETE_CANCELLED", "");', 'define("APL_DELETE_CRACKED", "YES");');
            $config_values_good_array=array('define("APL_SALT", "'.generateSalt(16).'");', 'define("APL_ROOT_URL", "'.$ROOT_URL.'");', 'define("APL_PRODUCT_ID", '.$product_id.');', 'define("APL_DAYS", '.$config_apl_days.');', 'define("APL_STORAGE", "'.$config_apl_storage.'");', 'define("APL_DATABASE_TABLE", "'.$config_apl_database_table.'");', 'define("APL_LICENSE_FILE_LOCATION", "'.$config_apl_license_file_location.'");', 'define("APL_MYSQL_FILE_LOCATION", "'.$config_apl_mysql_file_location.'");', 'define("APL_INCLUDE_KEY_CONFIG", "'.generateSalt(16).'");', 'define("APL_DELETE_CANCELLED", "'.$config_apl_delete_cancelled.'");', 'define("APL_DELETE_CRACKED", "'.$config_apl_delete_cracked.'");');
            $config_file_content=str_replace($config_values_bad_array, $config_values_good_array, $config_file_content);

            if (empty($config_file_content)) //no content
                {
                $error_detected=1;
                $error_details.="Configuration file is empty.<br>";
                }
            else //everything OK
                {
                $action_success=1;
                }
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="Invalid product ID, license verification period, license storage type, license file location, MySQL table name, or MySQL file location.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="Configuration file generated.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="The configuration file could not be generated because of this error:<br><br>$error_details";
        $page_message_class="alert alert-danger";
        }
    }


//set default values to be used when no values of essential variables are set or values need to be reset
if (empty($product_id) || !filter_var($product_id, FILTER_VALIDATE_INT)) {$product_id=0;}
if (empty($config_apl_days) || !filter_var($config_apl_days, FILTER_VALIDATE_INT)) {$config_apl_days=7;}
if (empty($config_apl_license_file_location)) {$config_apl_license_file_location="signature/license.key.example";}
if (empty($config_apl_mysql_file_location)) {$config_apl_mysql_file_location="mysql/mysql.php";}
if (empty($config_apl_database_table)) {$config_apl_database_table="user_data";}


$products_array=returnProductsDropdownArray($product_id);


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
