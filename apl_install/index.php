<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
if (version_compare(PHP_VERSION, "5.5.0", "<")) {require_once("../apl_modules/password_hash.php");} //load file with password verification functions when PHP <5.5 is used (file stored one directory up in /apl_modules directory)
require_once("../apl_modules/phpmillion_core.php");
require_once("../apl_modules/phpmillion_modules.php");
require_once("../apl_modules/phpmillion_plugins.php");
require_once("../apl_modules/apl_core_configuration.php");
require_once("../apl_modules/apl_core_functions.php");
require_once("../lib/swiftmailer/swift_required.php");
require_once("../lib/Twig/Autoloader.php");


$page_title="Installation";
$page_message="Enter your email address (will be used for administrator's profile), <a href='https://codecanyon.net/downloads' target='_blank'>purchase code</a>, installation URL, and click the 'Submit' button.";
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


$current_url=str_replace("/apl_install", "", getRootUrl(getCurrentUrl(0, array()), 0, 0, 0, 1));


//do some compatibility checks
if (!isset($submit_ok)) //do checks only before form is submitted, no need to display it after running installer
    {
    $compatibility_issue_detected=0;

    if (version_compare(PHP_VERSION, "5.3.0", "<"))
        {
        $compatibility_issue_detected=1;
        $compatibility_check_missing_component.="PHP 5.3 or higher<br>";
        }
    if (!function_exists("mysqli_fetch_all"))
        {
        $compatibility_issue_detected=1;
        $compatibility_check_missing_component.="MySQLi (with MySQLnd driver)<br>";
        }
    if (!function_exists("curl_init"))
        {
        $compatibility_issue_detected=1;
        $compatibility_check_missing_component.="cURL<br>";
        }
    if (!function_exists("mcrypt_encrypt") || !function_exists("mcrypt_decrypt"))
        {
        $compatibility_issue_detected=1;
        $compatibility_check_missing_component.="Mcrypt<br>";
        }

    if ($compatibility_issue_detected==1)
        {
        $page_message.="<br><br><b>Attention!</b> Installation wizard could not find these PHP components on your server:<br>$compatibility_check_missing_component<br>If you really know these components are installed and function properly, proceed to installation at your own risk. Otherwise, server configuration should be inspected first.";
        $page_message_class="alert alert-warning";
        }


    $version_check_content=customPost(APL_ROOT_URL."/apl_callbacks/version_check.php", $current_url, "product_id=".rawurlencode(APL_PRODUCT_ID)."&product_version=".rawurlencode($PRODUCT_VERSION)."&connection_hash=".rawurlencode(hash("sha256", "version_check")));
    if (!empty($version_check_content))
        {
        $latest_product_version=parseXmlTags($version_check_content, "version_check");
        if (!empty($latest_product_version)) //latest version parsed successfully
            {
            if ($latest_product_version!=$PRODUCT_VERSION) //new version available
                {
                $page_message.="<br><br><b>Attention!</b> You are trying to install $PRODUCT_NAME version $PRODUCT_VERSION, while the latest version is $latest_product_version.<br><br>Consider downloading the current version for new features, bug fixes, and performance improvements, or proceed to installation at your own risk (this installation will not be supported).";
                $page_message_class="alert alert-warning";
                }
            }
        else //parsing failed
            {
            $page_message.="<br><br><b>Attention!</b> Impossible to parse the latest $PRODUCT_NAME version. If you can open $PRODUCT_NAME website by <a href='$PRODUCT_HOMEPAGE' target='_blank'>clicking this link</a>, but the same error is still displayed, contact us for assistance or proceed to installation at your own risk (this installation will not be supported). If website can't be opened, try again later.";
            $page_message_class="alert alert-danger";
            }
        }
    else //no connection
        {
        $page_message.="<br><br><b>Attention!</b> Impossible to connect to $PRODUCT_NAME server. If you can open $PRODUCT_NAME website by <a href='$PRODUCT_HOMEPAGE' target='_blank'>clicking this link</a>, but the same error is still displayed, contact us for assistance or proceed to installation at your own risk (this installation will not be supported). If website can't be opened, try again later.";
        $page_message_class="alert alert-danger";
        }
    }


if (isset($submit_ok))
    {
    if (filter_var($CLIENT_EMAIL, FILTER_VALIDATE_EMAIL) && filter_var($ROOT_URL, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) && ctype_alnum(substr($ROOT_URL, -1)))
        {
        $apl_core_notifications=aplCheckSettings(); //check core settings
        if (!empty($apl_core_notifications))
            {
            $error_detected=1;
            $error_details.=implode("<br>", $apl_core_notifications);
            }

        if ($error_detected!=1)
            {
            $post_info_purchase="product_id=".rawurlencode(APL_PRODUCT_ID)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&connection_hash=".rawurlencode(hash("sha256", "verify_purchase")); //format variables used to verify purchase
            $purchase_verification=customPost(APL_ROOT_URL."/apl_callbacks/verify_purchase.php", $ROOT_URL, $post_info_purchase);


            $license_notifications_array=aplInstallLicense($GLOBALS["mysqli"], $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE); //install license
            if ($license_notifications_array['notification_case']=="notification_license_ok")
                {
                $INSTALLATION_HASH=hash("sha256", $ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE); //generate hash

                $post_info_mysql="product_id=".rawurlencode(APL_PRODUCT_ID)."&product_version=".rawurlencode($PRODUCT_VERSION)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode(aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)); //format variables used to get MySQL query
                $product_install_query=customPost(APL_ROOT_URL."/apl_callbacks/mysql_install.php", $ROOT_URL, $post_info_mysql);
                if (!empty($product_install_query))
                    {
                    $notifications_array=parseLicenseNotifications($product_install_query); //parse <notification_case> along with message
                    if (!empty($notifications_array['notification_case']) && !empty($notifications_array['notification_text'])) //a specific error notification returned
                        {
                        $error_detected=1;
                        $error_details.=$notifications_array['notification_text'].".<br>";
                        }
                    else //raw installation query returned
                        {
                        $mysql_bad_array=array("_ROOT_URL_", "_CLIENT_EMAIL_", "_LICENSE_CODE_", "_INSTALLATION_HASH_", "_ADMIN_PASSWORD_", "_CURRENT_DATE_", "_DATABASE_VERSION_");
                        $mysql_good_array=array($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE, $INSTALLATION_HASH, password_hash("phpmillion", PASSWORD_DEFAULT), date("Y-m-d"), $PRODUCT_VERSION);
                        $product_install_query=str_replace($mysql_bad_array, $mysql_good_array, $product_install_query); //replace some variables with actual values

                        mysqli_close($GLOBALS["mysqli"]); //close MySQL connection and connect again to prevent errors when mysqli_query is executed again (1st query was executed by aplInstallLicense() function)
                        sleep(1);

                        $GLOBALS["mysqli"]=mysqli_connect($DB_HOST, $DB_USER, $DB_PASS);
                        mysqli_select_db($GLOBALS["mysqli"], $DB_NAME);
                        mysqli_query($GLOBALS["mysqli"], "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
                        mysqli_set_charset($GLOBALS["mysqli"], "utf8");

                        mysqli_multi_query($GLOBALS["mysqli"], $product_install_query) or die(mysqli_error($GLOBALS["mysqli"]));
                        $action_success=1; //if script wasn't aborted by die() function above, it means everything was just fine
                        }
                    }
                else
                    {
                    $error_detected=1;
                    $error_details.="Impossible to connect to $PRODUCT_NAME server to parse installation query.<br>";
                    }
                }
            else //something went wrong
                {
                $error_detected=1;
                $error_details.=$license_notifications_array['notification_text'];
                }
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="Invalid email address or installation URL.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="$PRODUCT_NAME successfully installed.<br><br>Now delete the /apl_install directory and <a href='$ROOT_URL/apl_admin'><b>click here</b></a> to enter administration dashboard. Use email address <b>$CLIENT_EMAIL</b> and password <b>phpmillion</b> to login.";
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="$PRODUCT_NAME could not be installed because of this error:<br><br>$error_details";
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
