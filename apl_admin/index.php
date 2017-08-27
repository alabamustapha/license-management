<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Dashboard";


//select some stuff for stats
$quick_stats_results=mysqli_query($GLOBALS["mysqli"], "SELECT
(SELECT count(*) FROM apl_products) AS 'TOTAL_PRODUCTS',
(SELECT count(*) FROM apl_products WHERE product_status='1') AS 'TOTAL_ACTIVE_PRODUCTS',
(SELECT count(*) FROM apl_clients) AS 'TOTAL_CLIENTS',
(SELECT count(*) FROM apl_clients WHERE client_status='1') AS 'TOTAL_ACTIVE_CLIENTS',
(SELECT count(*) FROM apl_licenses) AS 'TOTAL_LICENSES',
(SELECT count(*) FROM apl_licenses WHERE license_status='1') AS 'TOTAL_ACTIVE_LICENSES',
(SELECT count(*) FROM apl_callbacks) AS 'TOTAL_CALLBACKS',
(SELECT count(*) FROM apl_callbacks WHERE callback_status='1') AS 'TOTAL_SUCCESSSFUL_CALLBACKS'");

$quick_stats_results_row=mysqli_fetch_assoc($quick_stats_results);
foreach ($quick_stats_results_row as $key=>$value)
    {
    $$key=$value;
    }


//return latest clients
function returnLatestClientsArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT *, (SELECT COUNT(*) FROM apl_licenses WHERE apl_licenses.client_id=apl_clients.client_id) AS total_licenses FROM apl_clients ORDER BY client_id DESC LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['client_status_formatted']=returnFormattedStatusArray($item_array['client_status'], "Active", "Inactive", "Suspended");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return latest licenses
function returnyLatestLicensesArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_licenses JOIN apl_products ON apl_licenses.product_id=apl_products.product_id LEFT JOIN apl_clients ON apl_clients.client_id=apl_licenses.client_id ORDER BY license_id DESC LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        if (verifyDate($item_array['license_expire_date'], "Y-m-d") && $item_array['license_expire_date']<date("Y-m-d")) {$item_array['license_status']=2;} //expired status will be formatted
        if (!verifyDate($item_array['license_expire_date'], "Y-m-d")) {$item_array['license_expire_date']="";}

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['license_status_formatted']=returnFormattedStatusArray($item_array['license_status'], "Active", "Inactive", "Expired");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return latest installations
function returnLatestInstallationsArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_installations JOIN apl_products ON apl_installations.product_id=apl_products.product_id LEFT JOIN apl_clients ON apl_clients.client_id=apl_installations.client_id ORDER BY installation_id DESC LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['installation_status_formatted']=returnFormattedStatusArray($item_array['installation_status'], "Active", "Inactive", "Unknown");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return latest callbacks
function returnLatestCallbacksArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_callbacks JOIN apl_products ON apl_callbacks.product_id=apl_products.product_id LEFT JOIN apl_clients ON apl_clients.client_id=apl_callbacks.client_id ORDER BY callback_id DESC LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['callback_status_formatted']=returnFormattedStatusArray($item_array['callback_status'], "Success", "Failure", "Unknown");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return expiring licenses
function returnExpiringLicensesArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_licenses JOIN apl_products ON apl_licenses.product_id=apl_products.product_id AND apl_licenses.license_expire_date>='".date("Y-m-d")."' AND apl_licenses.license_status='1' LEFT JOIN apl_clients ON apl_clients.client_id=apl_licenses.client_id ORDER BY license_expire_date LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['license_status_formatted']=returnFormattedStatusArray($item_array['license_status'], "Active", "Inactive", "Expired");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


//return expiring updates
function returnExpiringUpdatesArray($RECORDS_ON_INDEX_PAGE)
    {
    $root_array=array();

    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_licenses JOIN apl_products ON apl_licenses.product_id=apl_products.product_id AND apl_licenses.license_updates_date>='".date("Y-m-d")."' AND apl_licenses.license_status='1' LEFT JOIN apl_clients ON apl_clients.client_id=apl_licenses.client_id ORDER BY license_updates_date LIMIT $RECORDS_ON_INDEX_PAGE");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $item_array[$key]=$value;
            }

        if (verifyDate($item_array['license_updates_date'], "Y-m-d"))
            {
            if ($item_array['license_updates_date']<date("Y-m-d")) {$item_array['updates_status']=2;} //expired status will be formatted
            else {$item_array['updates_status']=1;} //active status will be formatted
            }

        if (!verifyDate($item_array['license_updates_date'], "Y-m-d")) {$item_array['license_updates_date']="";}

        $item_array['client_formatted']=formatClient($item_array['client_id'], $item_array['client_fname'], $item_array['client_lname'], $item_array['client_email'], $item_array['license_code']);
        $item_array['license_status_formatted']=returnFormattedStatusArray($item_array['updates_status'], "Active", "Inactive", "Expired");

        $root_array[]=$item_array;
        }

    return $root_array;
    }


$latest_clients_array=returnLatestClientsArray($RECORDS_ON_INDEX_PAGE);
$latest_licenses_array=returnyLatestLicensesArray($RECORDS_ON_INDEX_PAGE);
$latest_installations_array=returnLatestInstallationsArray($RECORDS_ON_INDEX_PAGE);
$latest_callbacks_array=returnLatestCallbacksArray($RECORDS_ON_INDEX_PAGE);
$expiring_licenses_array=returnExpiringLicensesArray($RECORDS_ON_INDEX_PAGE);
$expiring_updates_array=returnExpiringUpdatesArray($RECORDS_ON_INDEX_PAGE);


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
