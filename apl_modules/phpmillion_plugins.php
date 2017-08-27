<?php
//custom plugins used by this app only


//create license report
function createLicenseReport($report_text, $account_id, $license_code, $report_failure)
    {
    $stmt=mysqli_prepare($GLOBALS["mysqli"], "INSERT INTO apl_reports (account_id, license_code, report_date, report_text, report_failure) VALUES (?, ?, ?, ?, ?)");
    if ($stmt)
        {
        $report_date=date("Y-m-d");
        $report_system=0; //license reports should never be system

        mysqli_stmt_bind_param($stmt, "isssi", $account_id, $license_code, $report_date, $report_text, $report_failure);
        $exec=mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        }
    }


//generate signature to be displayed for remote script
function generateServerSignature($product_id, $root_url, $client_email, $license_code)
    {
    global $ROOT_URL;

    $license_signature=hash("sha256", implode("", (gethostbynamel(getRawDomain($ROOT_URL)))).$product_id.$license_code.$client_email.$root_url.gmdate("Y-m-d"));

    return $license_signature;
    }


//verify signature received from user's script (used in apl_install_license, apl_verify_license and apl_verify_updates functions of user's script)
function verifyScriptSignature($license_signature, $product_id, $root_url, $client_email, $license_code)
    {
    global $ROOT_URL;

    $signature_ok=false;

    if (!empty($license_signature) && hash("sha256", gmdate("Y-m-d").$root_url.$client_email.$license_code.$product_id.implode("", (gethostbynamel(getRawDomain($ROOT_URL)))))==$license_signature)
        {
        $signature_ok=true;
        }

    return $signature_ok;
    }


//return required license notification and format variables. also return license signature when everything OK
function returnLicenseNotification($notification_case, $root_url, $ip_address, $client_email, $license_code, $product_id, $product_title, $product_version, $license_expire_date, $license_cancel_date, $license_updates_date, $license_limit)
    {
    $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_notifications WHERE notification_id='1'");
    while ($row=mysqli_fetch_assoc($results))
        {
        foreach ($row as $key=>$value)
            {
            $$key=$value;

            if ($key==$notification_case) //all messages are selected by default (since all of them are stored in a single row), return only single message that is needed
                {
                $bad_text_array=array("%ROOT_URL%", "%IP_ADDRESS%", "%CLIENT_EMAIL%", "%LICENSE_CODE%", "%PRODUCT_ID%", "%PRODUCT_TITLE%", "%PRODUCT_VERSION%", "%LICENSE_EXPIRE_DATE%", "%LICENSE_CANCEL_DATE%", "%LICENSE_UPDATES_DATE%", "%LICENSE_LIMIT%");
                $good_text_array=array($root_url, $ip_address, $client_email, $license_code, $product_id, $product_title, $product_version, $license_expire_date, $license_cancel_date, $license_updates_date, $license_limit);

                $notification_text=str_ireplace($bad_text_array, $good_text_array, "<$notification_case>".$$notification_case."</$notification_case>"); //wrap message into tags like <notification_license...>
                }
            }
        }

    if ($notification_case=="notification_license_ok") //everything OK, also return license signature
        {
        $notification_text.="<license_signature>".generateServerSignature($product_id, $root_url, $client_email, $license_code)."</license_signature>";
        }

    return $notification_text;
    }


//format client link
function formatClient($client_id, $client_fname, $client_lname, $client_email, $license_code)
    {
    if (filter_var($client_id, FILTER_VALIDATE_INT))
        {
        $client_formatted="<a href='clients_edit.php?client_id=$client_id'>$client_fname $client_lname ($client_email)</a>";
        }
    else
        {
        $client_formatted=$license_code;
        }

    return $client_formatted;
    }