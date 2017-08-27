<?php
//used to check if updates are active
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
if (filter_var($ip_address, FILTER_VALIDATE_IP) && in_array($user_agent, $SUPPORTED_BROWSERS_ARRAY) && FILTER_VAR($product_id, FILTER_VALIDATE_INT) && filter_var($root_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) && $root_url==$refer && !empty($installation_hash) && $installation_hash==hash("sha256", $root_url.$client_email.$license_code) && !empty($license_signature))
    {
    $license_results_total=0;

    if (filter_var($client_email, FILTER_VALIDATE_EMAIL)) //always search for email-based license first
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_licenses JOIN apl_products ON apl_licenses.product_id=apl_products.product_id JOIN apl_clients ON apl_licenses.client_id=apl_clients.client_id WHERE apl_products.product_id=? AND apl_clients.client_email=? AND apl_licenses.client_id IS NOT NULL AND apl_licenses.license_code IS NULL");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "is", $product_id, $client_email);
            $exec=mysqli_stmt_execute($stmt);
            $license_results=mysqli_stmt_get_result($stmt);
            $license_results_total=mysqli_num_rows($license_results);
            mysqli_stmt_close($stmt);
            }
        }

    if ($license_results_total<1 && !empty($license_code)) //email-based license does not exist, try code-based (if code was submitted)
        {
        $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_licenses JOIN apl_products ON apl_licenses.product_id=apl_products.product_id WHERE apl_products.product_id=? AND apl_licenses.client_id IS NULL AND apl_licenses.license_code IS NOT NULL AND apl_licenses.license_code=?");
        if ($stmt)
            {
            mysqli_stmt_bind_param($stmt, "is", $product_id, $license_code);
            $exec=mysqli_stmt_execute($stmt);
            $license_results=mysqli_stmt_get_result($stmt);
            $license_results_total=mysqli_num_rows($license_results);
            mysqli_stmt_close($stmt);
            }
        }

    if ($license_results_total<1) //neither email-based or code-based license exists. since no product or license exist, set default values to "null" to prevent "undefined variable" errors
        {
        $product_title=null;
        $product_version=null;
        $license_expire_date=null;
        $license_cancel_date=null;
        $license_updates_date=null;
        $license_limit=null;

        $notification_case="notification_license_not_found";
        $error_detected=1;
        $error_reason="license not found";
        echo returnLicenseNotification($notification_case, $root_url, $ip_address, $client_email, $license_code, $product_id, $product_title, $product_version, $license_expire_date, $license_cancel_date, $license_updates_date, $license_limit);
        exit();
        }


    $license_row=mysqli_fetch_assoc($license_results); //fetch license details
    foreach ($license_row as $license_array_key=>$license_array_value)
        {
        $$license_array_key=$license_array_value;
        }


    $installation_domain=getRootUrl("$root_url/", 1, 1, 0, 1); //make url without scheme, www. and / at the end because this type of url is stored on server (add / at the end before processing because software stores root url without /)
    if (filter_var($client_id, FILTER_VALIDATE_INT)) {$client_formatted="$client_fname $client_lname ($client_email)";}
    else {$client_formatted=$license_code;}


    if (!verifyScriptSignature($license_signature, $product_id, $root_url, $client_email, $license_code)) //invalid signature
        {
        $notification_case="notification_invalid_signature";
        $error_detected=1;
        $error_reason="invalid license signature";
        }

    if ($product_status!=1) //product inactive
        {
        $notification_case="notification_product_inactive";
        $error_detected=1;
        $error_reason="product is inactive";
        }

    if ($license_status==0) //license cancelled
        {
        $notification_case="notification_license_cancelled";
        $error_detected=1;
        $error_reason="license cancelled on $license_cancel_date";
        }

    if ($license_status==2) //license suspended
        {
        $notification_case="notification_license_suspended";
        $error_detected=1;
        $error_reason="license suspended";
        }

    if (verifyDate($license_expire_date, "Y-m-d") && $license_expire_date<date("Y-m-d")) //license expired
        {
        $notification_case="notification_license_expired";
        $error_detected=1;
        $error_reason="license expired on $license_expire_date";
        }

    if (!empty($license_ip))
        {
        $license_ip_array=explode(",", $license_ip);
        if (!in_array($ip_address, $license_ip_array)) //invalid IP
            {
            $notification_case="notification_invalid_ip";
            $error_detected=1;
            $error_reason="IP address $ip_address is not allowed";
            }
        }

    if (!empty($license_domain))
        {
        $license_domain_detected=0; //will be changed to 1 later only if everything OK
        $license_domain_array=explode(",", $license_domain);
        foreach ($license_domain_array as $license_domain_array_key=>$license_domain_array_value)
            {
            if (stristr(getRootUrl("$root_url/", 1, 1, 0, 1), $license_domain_array_value)) //check if URL (where script is installed) matches one of allowed URLs (add / at the end before processing because software stores root url without /)
                {
                $license_domain_detected=1;
                break;
                }
            }

        if ($license_domain_detected!=1) //invalid domain
            {
            $notification_case="notification_invalid_domain";
            $error_detected=1;
            $error_reason="domain $root_url is invalid";
            }
        }

    if ($license_require_domain==1 && filter_var(getRawDomain($root_url), FILTER_VALIDATE_IP) || $license_require_domain==1 && !filter_var($root_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) //domain required
        {
        $notification_case="notification_domain_required";
        $error_detected=1;
        $error_reason="a real domain is required";
        }

    if (!empty($installation_domain) && !empty($ip_address)) //check if installation on this domain and IP is not performed by someone else
        {
        if (filter_var($client_id, FILTER_VALIDATE_INT)) //client_id is not null (so license_code is null), search by client_id (prepared statements has limitations when using where clauses with null values)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND installation_ip=? AND installation_domain=? AND (client_id IS NULL OR client_id!=?)");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "issi", $product_id, $ip_address, $installation_domain, $client_id);
                $exec=mysqli_stmt_execute($stmt);
                $domain_in_use_results=mysqli_stmt_get_result($stmt);
                $domain_in_use_results_total=mysqli_num_rows($domain_in_use_results);
                mysqli_stmt_close($stmt);
                }
            }
        else //client_id is null (so license_code is not null), search by license_code (prepared statements has limitations when using where clauses with null values)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND installation_ip=? AND installation_domain=? AND (license_code IS NULL OR license_code!=?)");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "isss", $product_id, $ip_address, $installation_domain, $license_code);
                $exec=mysqli_stmt_execute($stmt);
                $domain_in_use_results=mysqli_stmt_get_result($stmt);
                $domain_in_use_results_total=mysqli_num_rows($domain_in_use_results);
                mysqli_stmt_close($stmt);
                }
            }

        if ($domain_in_use_results_total>0)
            {
            $notification_case="notification_domain_in_use";
            $error_detected=1;
            $error_reason="product $product_title is already installed on domain $root_url ($ip_address) by another client";
            }
        }

    if ($license_limit!=0) //check if installations limit is not exceeded
        {
        if (filter_var($client_id, FILTER_VALIDATE_INT)) //client_id is not null (so license_code is null), search by client_id (prepared statements has limitations when using where clauses with null values)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND client_id=? AND (installation_ip!=? OR installation_domain!=?)");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "iiss", $product_id, $client_id, $ip_address, $installation_domain);
                $exec=mysqli_stmt_execute($stmt);
                $client_concurrent_installation_results=mysqli_stmt_get_result($stmt);
                $client_concurrent_installation_results_total=mysqli_num_rows($client_concurrent_installation_results);
                mysqli_stmt_close($stmt);
                }
            }
        else //client_id is null (so license_code is not null), search by license_code (prepared statements has limitations when using where clauses with null values)
            {
            $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND license_code=? AND (installation_ip!=? OR installation_domain!=?)");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "isss", $product_id, $license_code, $ip_address, $installation_domain);
                $exec=mysqli_stmt_execute($stmt);
                $client_concurrent_installation_results=mysqli_stmt_get_result($stmt);
                $client_concurrent_installation_results_total=mysqli_num_rows($client_concurrent_installation_results);
                mysqli_stmt_close($stmt);
                }
            }

        if ($client_concurrent_installation_results_total>=$license_limit) //client already performed maximum allowed number of installations on OTHER domains, so this one would exceed limit
            {
            $notification_case="notification_license_limit";
            $error_detected=1;
            $error_reason="maximum installations limit $license_limit reached";
            }
        }

    if (verifyDate($license_updates_date, "Y-m-d") && $license_updates_date<date("Y-m-d")) //updates expired - THIS LINE SHOULD ONLY BE USED IN LINCENSE_UPDATES.PHP and SOFTWARE_DOWNLOAD.PHP FILES
        {
        $notification_case="notification_updates_expired";
        $error_detected=1;
        $error_reason="updates expired on $license_updates_date";
        }


    if ($error_detected!=1) //everything OK so far, do final check if license is really active
        {
        if ($license_status==1) //license active, check if installation is stored in database
            {
            if (filter_var($client_id, FILTER_VALIDATE_INT)) //client_id is not null (so license_code is null), search by client_id (prepared statements has limitations when using where clauses with null values)
                {
                $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND client_id=? AND installation_ip=? AND installation_domain=? AND installation_hash=?");
                if ($stmt)
                    {
                    mysqli_stmt_bind_param($stmt, "iisss", $product_id, $client_id, $ip_address, $installation_domain, $installation_hash);
                    $exec=mysqli_stmt_execute($stmt);
                    $selected_installation_results=mysqli_stmt_get_result($stmt);
                    $selected_installation_results_total=mysqli_num_rows($selected_installation_results);
                    mysqli_stmt_close($stmt);
                    }
                }
            else //client_id is null (so license_code is not null), search by license_code (prepared statements has limitations when using where clauses with null values)
                {
                $stmt=mysqli_prepare($GLOBALS["mysqli"], "SELECT * FROM apl_installations WHERE product_id=? AND license_code=? AND installation_ip=? AND installation_domain=? AND installation_hash=?");
                if ($stmt)
                    {
                    mysqli_stmt_bind_param($stmt, "issss", $product_id, $license_code, $ip_address, $installation_domain, $installation_hash);
                    $exec=mysqli_stmt_execute($stmt);
                    $selected_installation_results=mysqli_stmt_get_result($stmt);
                    $selected_installation_results_total=mysqli_num_rows($selected_installation_results);
                    mysqli_stmt_close($stmt);
                    }
                }

            if ($selected_installation_results_total>0) //installation exists
                {
                $action_success=1;
                $report_text="$product_title installation for $client_formatted on $installation_domain ($ip_address) is eligible for updates.";
                }
            else //installation does not exist
                {
                $notification_case="notification_installation_not_found";
                $error_detected=1;
                $error_reason="installation does not exist";
                }
            }
        }


    if ($action_success==1) //everything OK (license_signature will also be displayed in this case)
        {
        $notification_case="notification_license_ok"; //$report_text is already created above in this case
        }
    else
        {
        $report_text="$product_title installation for $client_formatted on $installation_domain ($ip_address) is not eligible for updates because of this reason: $error_reason."; //$notification_case is already created above in this case
        }


    echo returnLicenseNotification($notification_case, $root_url, $ip_address, $client_email, $license_code, $product_id, $product_title, $product_version, $license_expire_date, $license_cancel_date, $license_updates_date, $license_limit);
    createLicenseReport($report_text, $client_id, $license_code, $error_detected); //creates extended report
    }
