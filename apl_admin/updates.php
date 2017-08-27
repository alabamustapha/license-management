<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Software Updates";


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


if (!isset($submit_ok)) //check for latest version only if upgrade wasn't submitted
    {
    $version_check_content=customPost(APL_ROOT_URL."/apl_callbacks/version_check.php", $ROOT_URL, "product_id=".rawurlencode(APL_PRODUCT_ID)."&product_version=".rawurlencode($PRODUCT_VERSION)."&connection_hash=".rawurlencode(hash("sha256", "version_check")));
    if (!empty($version_check_content))
        {
        $latest_product_version=parseXmlTags($version_check_content, "version_check");
        if (!empty($latest_product_version)) //latest version parsed successfully
            {
            if ($latest_product_version!=$PRODUCT_VERSION) //new version available
                {
                $upgrade_available=1;

                $page_message="You have $PRODUCT_NAME version $PRODUCT_VERSION installed. The latest version is $latest_product_version. Consider upgrading to new version for new features, bug fixes, and performance improvements.<br><br>Your installation can be automatically upgraded in seconds by clicking the 'Upgrade Now' button. Upgrade will keep all your settings and data.";
                $page_message_class="alert alert-warning";
                }
            else //no newer version available
                {
                $page_message="You have the latest $PRODUCT_NAME version installed! Once a new version is available, automatic upgrade option will appear below.";
                $page_message_class="alert alert-success";
                }
            }
        else //parsing failed
            {
            $page_message="Impossible to parse the latest $PRODUCT_NAME version. Try again later...";
            $page_message_class="alert alert-warning";
            }
        }
    else //no connection
        {
        $page_message="Impossible to connect to $PRODUCT_NAME server. Try again later...";
        $page_message_class="alert alert-danger";
        }
    }


if (isset($submit_ok))
    {
    $post_info="product_id=".rawurlencode(APL_PRODUCT_ID)."&product_version=".rawurlencode($PRODUCT_VERSION)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode(aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));
    $upgrade_archive_content=customPost(APL_ROOT_URL."/apl_callbacks/software_download.php", $ROOT_URL, $post_info); //will return zip file on success or error message on failure
    if (!empty($upgrade_archive_content))
        {
        if (substr($upgrade_archive_content, 0, 2)!="PK") //something else instead of zip archive returned
            {
            if (stristr($upgrade_archive_content, "<notification_") && stristr($upgrade_archive_content, "</notification_")) //a specific error notification returned
                {
                $notifications_array=parseLicenseNotifications($upgrade_archive_content); //parse <notification_case> along with message

                $error_detected=1;
                $error_details.=$notifications_array['notification_text'].".<br>";
                }
            else
                {
                $error_detected=1;
                $error_details.="No valid content from $PRODUCT_NAME server received.<br>";
                }
            }
        else //everything ok, zip archive returned
            {
            $script_root_directory=dirname(dirname(__FILE__)); //get script's root directory
            $zip_file_name=str_ireplace(" ", "-", $PRODUCT_NAME).".zip"; //zip archive name should look as Product-Name.zip
            $upgrade_archive_local_destination="$script_root_directory/$zip_file_name"; //download archive right to the script's root directory

            $zip_file=@fopen($upgrade_archive_local_destination, "w+");
            $fwrite=@fwrite($zip_file, $upgrade_archive_content);
            if ($fwrite===false) //saving upgrade archive failed
                {
                $error_detected=1;
                $error_details.="Impossible to save upgrade archive at $script_root_directory.<br>";
                }
            else //upgrade archive saved, extract it
                {
                @fclose($zip_file);
                $zip_file=new ZipArchive;
                if ($zip_file->open("$script_root_directory/$zip_file_name")!=true) //upgrade archive can't be opened
                    {
                    $error_detected=1;
                    $error_details.="Impossible to extract upgrade archive at $script_root_directory.<br>";
                    }
                else //everything ok, extract upgrade archive
                    {
                    $zip_file->extractTo($script_root_directory);
                    $zip_file->close();
                    $local_upgrade_content=customGet("$ROOT_URL/apl_upgrade.php", "", ""); //launch upgrade file, it will execute MySQL upgrade query and/or perform other actions in background
                    if (!empty($local_upgrade_content))
                        {
                        $upgraded_product_version=parseXmlTags($local_upgrade_content, "upgrade");
                        if (!empty($upgraded_product_version)) //upgrade succeeded
                            {
                            @unlink("$script_root_directory/apl_config_sample.php"); //delete sample config file
                            @unlink("$script_root_directory/apl_upgrade.php"); //delete upgrade file
                            @unlink("$script_root_directory/$zip_file_name"); //delete upgrade archive
                            $action_success=1;
                            }
                        else //upgrade failed
                            {
                            $error_detected=1;
                            $error_details.="Upgrade script at $ROOT_URL/apl_upgrade.php failed.<br>";
                            }
                        }
                    else //no connection
                        {
                        $error_detected=1;
                        $error_details.="Impossible to connect to upgrade script at $ROOT_URL/apl_upgrade.php.<br>";
                        }
                    }
                }
            }
        }
    else
        {
        $error_detected=1;
        $error_details.="Impossible to connect to $PRODUCT_NAME server to download the latest version.<br>";
        }

    if ($action_success==1) //everything OK
        {
        $page_message="$PRODUCT_NAME updated from version $PRODUCT_VERSION to $upgraded_product_version.";
        createReport(strip_tags($page_message), $admin_id, 1, 0); //creates extended report
        $page_message_class="alert alert-success";
        }
    else //display error message
        {
        $page_message="$PRODUCT_NAME could not be updated because of this error:<br><br>$error_details";
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
