<?php
$DB_HOST="localhost";
$DB_NAME="licenser";
$DB_USER="homestead";
$DB_PASS="secret";

$GLOBALS["mysqli"]=mysqli_connect($DB_HOST, $DB_USER, $DB_PASS);
mysqli_select_db($GLOBALS["mysqli"], $DB_NAME);
mysqli_query($GLOBALS["mysqli"], "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
mysqli_set_charset($GLOBALS["mysqli"], "utf8");
