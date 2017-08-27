<?php
//contains and executes MySQL upgrade queries for each version, does other upgrade actions if needed
require_once("apl_config.php");
require_once("apl_ver.php");
require_once("apl_settings.php");


if (isset($_POST)) {$post_values_array=$_POST;} //super variable with all POST variables
if (!empty($post_values_array) && is_array($post_values_array))
    {
    foreach ($post_values_array as $post_values_key=>$post_values_value)
        {
        if (!isset($$post_values_key)) {if (!is_array($post_values_value)) {$$post_values_key=removeInvisibleHtml($post_values_value);} else {$$post_values_key=array_map("removeInvisibleHtml", $post_values_value);}} //sanitize data (don't overwrite existing variables)
        }
    }


$mysql_query="";
$version_number_update_query="UPDATE `apl_settings` SET `DATABASE_VERSION`='$PRODUCT_VERSION';"; //this line will always be added at the end of mysql_query to update DATABASE_VERSION


if (empty($DATABASE_VERSION) || $DATABASE_VERSION=="1.0") //MySQL upgrade query (if any) for version 1.0
    {
    $mysql_query="ALTER TABLE `apl_settings` ADD `DATABASE_VERSION` VARCHAR(250) NOT NULL AFTER `NEWS_DATE`;

    ALTER TABLE `apl_licenses` ADD INDEX `client_id` (`client_id`);
    ALTER TABLE `apl_licenses` ADD INDEX `product_id` (`product_id`);

    ALTER TABLE `apl_installations` ADD INDEX `client_id` (`client_id`);
    ALTER TABLE `apl_installations` ADD INDEX `license_code` (`license_code`);
    ALTER TABLE `apl_installations` ADD INDEX `product_id` (`product_id`);";
    }


if (empty($DATABASE_VERSION) || $DATABASE_VERSION=="1.1") //MySQL upgrade query (if any) for version 1.1
    {
    $mysql_query="ALTER TABLE `apl_settings` ADD `DATABASE_VERSION` VARCHAR(250) NOT NULL AFTER `NEWS_DATE`;

    ALTER TABLE `apl_licenses` ADD INDEX `client_id` (`client_id`);
    ALTER TABLE `apl_licenses` ADD INDEX `product_id` (`product_id`);

    ALTER TABLE `apl_installations` ADD INDEX `client_id` (`client_id`);
    ALTER TABLE `apl_installations` ADD INDEX `license_code` (`license_code`);
    ALTER TABLE `apl_installations` ADD INDEX `product_id` (`product_id`);";
    }


$mysql_query.=$version_number_update_query; //add final line to update DATABASE_VERSION


if (!empty($mysql_query))
    {
    mysqli_close($GLOBALS["mysqli"]); //close MySQL connection and connect again to prevent errors when mysqli_query is executed again
    sleep(1);

    $GLOBALS["mysqli"]=mysqli_connect($DB_HOST, $DB_USER, $DB_PASS);
    mysqli_select_db($GLOBALS["mysqli"], $DB_NAME);
    mysqli_query($GLOBALS["mysqli"], "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    mysqli_set_charset($GLOBALS["mysqli"], "utf8");

    mysqli_multi_query($GLOBALS["mysqli"], $mysql_query) or die(mysqli_error($GLOBALS["mysqli"]));
    echo "<upgrade>$PRODUCT_VERSION</upgrade>"; //if script wasn't aborted by die() function above, it means everything was just fine
    }
