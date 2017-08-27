<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");


if (isset($_COOKIE))
    {
    foreach ($_COOKIE as $cookie_key=>$cookie_value)
        {
        setcookie($cookie_key, false, 1); //standard way of removing a cookie (thus you can't store false in a cookie)
        unset($_COOKIE[$cookie_key]); //remove cookie from script
        }
    }
header("Location: login.php");
exit();
