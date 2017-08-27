<?php
error_reporting(E_ALL);

//get all settings
$settings_results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_settings");
while ($settings_row=mysqli_fetch_assoc($settings_results))
    {
    foreach ($settings_row as $settings_key=>$settings_value)
        {
        $$settings_key=$settings_value;
        }
    }


//cookie for login system
$COOKIE_PREFIX="apl";

//supported browsers array (internal requests only coming from these browsers will be processed. do not modify yourself!)
$SUPPORTED_BROWSERS_ARRAY=array("Mozilla/5.0 (Windows NT 6.3; WOW64; rv:48.0) Gecko/20100101 Firefox/48.0");

//supported API functions array (only these API calls will be processed)
$SUPPORTED_API_FUNCTIONS_ARRAY=array("clients_add", "clients_edit", "installations_edit", "licenses_add", "licenses_edit", "products_add", "products_edit", "search");

//encrypt/decrypt text
define("SALT", "U953xSi4T27244H8");

//set timezone
date_default_timezone_set($TIMEZONE);

//load extra libraries
if (version_compare(PHP_VERSION, "5.5.0", "<")) {require_once("apl_modules/password_hash.php");} //load file with password verification functions when PHP <5.5 is used (file stored in /apl_modules directory)
require_once("apl_modules/phpmillion_core.php");
require_once("apl_modules/phpmillion_modules.php");
require_once("apl_modules/phpmillion_plugins.php");
require_once("apl_modules/apl_core_configuration.php");
require_once("apl_modules/apl_core_functions.php");
require_once("lib/swiftmailer/swift_required.php");
require_once("lib/Twig/Autoloader.php");

//verify APL settings
// $apl_core_notifications=aplCheckSettings(); //check core settings
// if (!empty($apl_core_notifications))
//     {
//     echo "Invalid settings";
//     exit();
//     }

// //verify APL data
// if (!aplCheckData($GLOBALS["mysqli"]))
//     {
//     echo "Invalid data";
//     exit();
//     }

// //verify APL license
// $license_notifications_array=aplVerifyLicense($GLOBALS["mysqli"], 0);
// if ($license_notifications_array['notification_case']!="notification_license_ok")
//     {
//     echo $license_notifications_array['notification_text'];
//     exit();
//     }

//load auto tasks
cleanupDatabase($DATABASE_CLEANUP_CALLBACKS, $DATABASE_CLEANUP_REPORTS, $DATABASE_CLEANUP_LICENSES, $DATABASE_CLEANUP_DAYS, $DATABASE_CLEANUP_DATE); //cleanup database if needed

//load RSS and sidebar menu items
$twig_rss_feeds_array=parseDisplayRss($NEWS_TEXT, "https://www.phpmillion.com/feed", $NEWS_DATE, 1, 3);
$twig_sidebar_items_array=returnLeftMenuArray(basename($_SERVER['SCRIPT_FILENAME']));
