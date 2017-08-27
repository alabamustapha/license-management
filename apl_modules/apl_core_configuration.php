<?php
//MAIN CONFIG FILE OF AUTO PHP LICENSER. CAN BE EDITED MANUALLY OR GENERATED USING Extra Tools > Configuration Generator TAB IN AUTO PHP LICENSER DASHBOARD. THE FILE MUST BE INCLUDED IN YOUR SCRIPT AND ENCODED BEFORE YOU PROVIDE A SCRIPT TO END USER.


//-----------BASIC SETTINGS-----------//


//Random salt used for encryption. It should contain 16 or 24 random symbols and be different for each application you want to protect. Cannot be modified after installing script.
define("APL_SALT", "8W862E7Q8dqWx26H");

//The URL (without / at the end) where Auto PHP Licenser from /WEB directory is installed on your server. No matter how many applications you want to protect, a single installation is enough.
define("APL_ROOT_URL", "http://licenser.app");

//Unique numeric ID of product that needs to be licensed. Can be obtained by going to Products > View Products tab in Auto PHP Licenser dashboard and selecting product to be licensed. At the end of URL, you will see something like products_edit.php?product_id=NUMBER, where NUMBER is unique product ID. Cannot be modified after installing script.
define("APL_PRODUCT_ID", 1);

//Time period (in days) between automatic license verifications. The lower the number, the more often license will be verified, but if many end users use your script, it can cause extra load on your server. Available values are between 1 and 365. Usually 7 or 14 days are the best choice.
define("APL_DAYS", 7);

//Place to store license signature and other details. "DATABASE" means data will be stored in MySQL database (recommended), "FILE" means data will be stored in local file. Only use "FILE" if your application doesn't support MySQL. Otherwise, "DATABASE" should always be used. Cannot be modified after installing script.
define("APL_STORAGE", "DATABASE");

//Name of table (will be automatically created during installation) to store license signature and other details. Only used when "APL_STORAGE" set to "DATABASE". The more "harmless" name, the better. Cannot be modified after installing script.
define("APL_DATABASE_TABLE", "apl_profiles");

//Name and location (relative to directory where "apl_core_configuration.php" file is located, cannot be moved outside this directory) of file to store license signature and other details. Can have ANY name and extension. The more "harmless" location and name, the better. Cannot be modified after installing script. Only used when "APL_STORAGE" set to "FILE" (file itself can be safely deleted otherwise).
define("APL_LICENSE_FILE_LOCATION", "signature/license.key.example");

//Name and location (relative to directory where "apl_core_configuration.php" file is located, cannot be moved outside this directory) of MySQL connection file. Only used when "APL_STORAGE" set to "DATABASE" (file itself can be safely deleted otherwise).
define("APL_MYSQL_FILE_LOCATION", "mysql/mysql.php");

//Notification to be displayed when license verification fails because of connection issues (no Internet connection, your domain is blacklisted by user, etc.) Other notifications will be automatically fetched from your Auto PHP Licenser installation.
define("APL_NOTIFICATION_NO_CONNECTION", "Can't connect to licensing server.");

//Notification to be displayed when updating database fails. Only used when APL_STORAGE set to DATABASE.
define("APL_NOTIFICATION_DATABASE_WRITE_ERROR", "Can't write to database.");

//Notification to be displayed when updating license file fails. Only used when APL_STORAGE set to FILE.
define("APL_NOTIFICATION_LICENSE_FILE_WRITE_ERROR", "Can't write to license file.");

//Notification to be displayed when installation wizard is launched again after script was installed.
define("APL_NOTIFICATION_SCRIPT_ALREADY_INSTALLED", "Script is already installed (or database not empty).");

//Notification to be displayed when license verification does not need to be performed. Used for debugging purposes only, should never be displayed to end user.
define("APL_NOTIFICATION_BYPASS_VERIFICATION", "No need to verify");


//-----------ADVANCED SETTINGS-----------//


//Secret key used to verify if configuration file included in your script is genuine (not replaced with 3rd party files). It can contain any number of random symbols and should be different for each application you want to protect. You should also change its name from "APL_INCLUDE_KEY_CONFIG" to something else, let's say "MY_CUSTOM_SECRET_KEY"
define("APL_CORE_CONFIGURATION_SECRET_KEY", "sDyeSGCm9n55mRjk");

//IP address of your Auto PHP Licenser installation. If IP address is set, script will always check if "APL_ROOT_URL" resolves to this IP address (very useful against users who may try blocking or nullrouting your domain on their servers). However, use it with caution because if IP address of your server is changed in future, old installations of your script will stop working (you will need to update this file with a new IP and send updated file to end user).
define("APL_ROOT_IP", "");

//When option set to "YES", all files and MySQL data will be deleted when illegal usage is detected. This is very useful against users who may try using pirated software; if someone shares his license with 3rd parties (by sending it to a friend, posting on warez forums, etc.) and you cancel this license, Auto PHP Licenser will try to delete all script files and any data in MySQL database for everyone who uses cancelled license. For obvious reasons, data will only be deleted if license is cancelled. If license is invalid or expired, no data will be modified. Use at your own risk!
define("APL_DELETE_CANCELLED", "NO");

//When option set to "YES", all files and MySQL data will be deleted when cracking attempt is detected. This is very useful against users who may try cracking software; if some unauthorized changes in core functions are detected, Auto PHP Licenser will try to delete all script files and any data in MySQL database. Use at your own risk!
define("APL_DELETE_CRACKED", "NO");


//-----------NOTIFICATIONS FOR DEBUGGING PURPOSES ONLY. SHOULD NEVER BE DISPLAYED TO END USER-----------//


define("APL_CORE_NOTIFICATION_INVALID_SALT", "Invalid or default encryption salt");
define("APL_CORE_NOTIFICATION_INVALID_ROOT_URL", "Invalid root URL of Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INACCESSIBLE_ROOT_URL", "Impossible to establish connection to your Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INVALID_PRODUCT_ID", "Invalid product ID");
define("APL_CORE_NOTIFICATION_INVALID_VERIFICATION_PERIOD", "Invalid license verification period");
define("APL_CORE_NOTIFICATION_INVALID_STORAGE", "Invalid license storage option");
define("APL_CORE_NOTIFICATION_INVALID_TABLE", "Invalid MySQL table name to store license signature");
define("APL_CORE_NOTIFICATION_INVALID_LICENSE_FILE", "Invalid license file location (or file not writable)");
define("APL_CORE_NOTIFICATION_INVALID_MYSQL_FILE", "Invalid MySQL file location (or file not readable)");
define("APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_NO_CONNECTION", "Notification (to be displayed when license verification fails because of connection) empty");
define("APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_DATABASE_WRITE_ERROR", "Notification (to be displayed when database update fails) empty");
define("APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_LICENSE_FILE_WRITE_ERROR", "Notification (to be displayed when license update fails) empty");
define("APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_SCRIPT_ALREADY_INSTALLED_ERROR", "Notification (to be displayed when script is already installed) empty");
define("APL_CORE_NOTIFICATION_INVALID_ROOT_IP", "Invalid IP address of your Auto PHP Licenser installation");
define("APL_CORE_NOTIFICATION_INVALID_DNS", "Domain of your Auto PHP Licenser installation does not match root IP address");


//-----------SOME EXTRA STUFF. SHOULD NEVER BE REMOVED OR MODIFIED-----------//
define("APL_DIRECTORY", __DIR__);
define("APL_MYSQL_QUERY", "LOCAL");
