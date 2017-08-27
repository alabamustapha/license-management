<?php
//slightly modified modules shared by most phpmillion apps


//create report
function createReport($report_text, $account_id, $report_system, $report_failure)
    {
    $stmt=mysqli_prepare($GLOBALS["mysqli"], "INSERT INTO apl_reports (account_id, report_date, report_text, report_system, report_failure) VALUES (?, ?, ?, ?, ?)");
    if ($stmt)
        {
        $report_date=date("Y-m-d");

        mysqli_stmt_bind_param($stmt, "issii", $account_id, $report_date, $report_text, $report_system, $report_failure);
        $exec=mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        }
    }


//remove old data
function cleanupDatabase($DATABASE_CLEANUP_CALLBACKS, $DATABASE_CLEANUP_REPORTS, $DATABASE_CLEANUP_LICENSES, $DATABASE_CLEANUP_DAYS, $DATABASE_CLEANUP_DATE)
    {
    $updated_records=0;
    $deleted_records_total=0;

    if ($DATABASE_CLEANUP_DAYS!=0 && $DATABASE_CLEANUP_DATE<date("Y-m-d")) //need to launch it only once a day
        {
        $old_date_allowed=date("Y-m-d", strtotime("-$DATABASE_CLEANUP_DAYS days")); //data older than this will be removed
        $report_text="Automatic database cleanup module launched. These records older than $DATABASE_CLEANUP_DAYS day(s) were removed: ";

        if ($DATABASE_CLEANUP_CALLBACKS==1)
            {
            $deleted_records=0;

            mysqli_query($GLOBALS["mysqli"], "DELETE FROM apl_callbacks WHERE callback_date<'$old_date_allowed'");
            $affected_rows=mysqli_affected_rows($GLOBALS["mysqli"]); if ($affected_rows>0) {$deleted_records=$affected_rows;}

            $report_text.="callbacks ($deleted_records records), ";
            $deleted_records_total=$deleted_records_total+$deleted_records;
            }

        if ($DATABASE_CLEANUP_REPORTS==1)
            {
            $deleted_records=0;

            mysqli_query($GLOBALS["mysqli"], "DELETE FROM apl_reports WHERE report_date<'$old_date_allowed'");
            $affected_rows=mysqli_affected_rows($GLOBALS["mysqli"]); if ($affected_rows>0) {$deleted_records=$affected_rows;}

            $report_text.="reports ($deleted_records records), ";
            $deleted_records_total=$deleted_records_total+$deleted_records;
            }

        if ($DATABASE_CLEANUP_LICENSES==1)
            {
            $deleted_records=0;

            mysqli_query($GLOBALS["mysqli"], "DELETE FROM apl_licenses WHERE license_status='0' AND license_cancel_date<'$old_date_allowed'");
            $affected_rows=mysqli_affected_rows($GLOBALS["mysqli"]); if ($affected_rows>0) {$deleted_records=$affected_rows;}

            $report_text.="inactive licenses ($deleted_records records), ";
            $deleted_records_total=$deleted_records_total+$deleted_records;
            }

        if ($deleted_records_total<1) //no records removed, generate a different report
            {
            $report_text="Automatic database cleanup module launched. No records older than $DATABASE_CLEANUP_DAYS day(s) were found  "; //2 last symbols will be cut when generating report
            }

        $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_settings SET DATABASE_CLEANUP_DATE=?");
        if ($stmt)
            {
            $database_cleanup_date=date("Y-m-d");

            mysqli_stmt_bind_param($stmt, "s", $database_cleanup_date);
            $exec=mysqli_stmt_execute($stmt);
            $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
            mysqli_stmt_close($stmt);
            }

        createReport(substr($report_text, 0, -2).".", 0, 1, 0);
        }
    }


//send notification email to admin(s)
function emailAdmin($email_subject, $email_text)
    {
    global $PRODUCT_NAME;

    if (!empty($email_subject) && !empty($email_text))
        {
        $transport=Swift_MailTransport::newInstance(); //create transport
        $mailer=Swift_Mailer::newInstance($transport); //create mailer using created transport
        $message=Swift_Message::newInstance("$PRODUCT_NAME: $email_subject") //create message
        ->setBody("This notification was sent by $PRODUCT_NAME to inform you about this event:<br><br>$email_text", "text/html");

        $results=mysqli_query($GLOBALS["mysqli"], "SELECT * FROM apl_admins");
        while ($row=mysqli_fetch_assoc($results))
            {
            foreach ($row as $key=>$value)
                {
                $$key=$value;
                }

            if (filter_var($admin_email, FILTER_VALIDATE_EMAIL))
                {
                $message->setFrom(array($admin_email=>$PRODUCT_NAME));
                $message->setTo(array($admin_email=>"$PRODUCT_NAME Admin"));

                $result=$mailer->send($message); //send message
                }
            }
        }
    }


//parse and display RSS feeds
function parseDisplayRss($NEWS_TEXT, $feed_url, $last_parsing_date, $days_between_parsing, $items_to_parse)
    {
    $final_results_total=0;
    $updated_records=0;

    if (empty($NEWS_TEXT) || getDaysBetweenDates($last_parsing_date, date("Y-m-d"))>=$days_between_parsing) //time to parse RSS feed
        {
        $parsed_results_array=array();
        $final_results_array=array();
        $content=customGet($feed_url, "", "");
        if (strlen($content)>100) //some content returned
            {
            $content_object=@simplexml_load_string($content);
            if (is_object($content_object))
                {
                foreach ($content_object as $entry)
                    {
                    foreach ($entry->item as $item)
                        {
                        $record['title']=(string) cleanupContent($item->title);
                        $record['full_url']=(string) trim(strip_tags($item->link));
                        $record['date']=(string) date("Y-m-d", strtotime(trim(strip_tags($item->pubDate))));
                        $parsed_results_array[]=$record;
                        }
                    }
                unset($content_object); //prevent leaking memory
                }

            $parsed_results_total=count($parsed_results_array);
            if ($parsed_results_total>0) //results parsed, make sub-arrays of titles, descriptions and links
                {
                foreach ($parsed_results_array as $key=>$value)
                    {
                    if (!empty($value['title']) && filter_var($value['full_url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) && $final_results_total<$items_to_parse)
                        {
                        $final_results_array[]=array("title"=>$value['title'], "full_url"=>$value['full_url'], "date"=>$value['date']);
                        }
                    }
                }

            $final_results_total=count($final_results_array);
            if ($final_results_total>0) //results parsed
                {
                $NEWS_TEXT=json_encode($final_results_array);
                }
            $NEWS_DATE=date("Y-m-d"); //update data even if no entries were parsed, so endless parsing doesn't occur

            $stmt=mysqli_prepare($GLOBALS["mysqli"], "UPDATE apl_settings SET NEWS_TEXT=?, NEWS_DATE=?");
            if ($stmt)
                {
                mysqli_stmt_bind_param($stmt, "ss", $NEWS_TEXT, $NEWS_DATE);
                $exec=mysqli_stmt_execute($stmt);
                $affected_rows=mysqli_stmt_affected_rows($stmt); if ($affected_rows>0) {$updated_records=$updated_records+$affected_rows;}
                mysqli_stmt_close($stmt);
                }
            }
        }

    if (!empty($NEWS_TEXT)) //display stored records
        {
        $NEWS_TEXT=convertObjectToArray(json_decode($NEWS_TEXT)); //decode and convert objects into arrays
        if (is_array($NEWS_TEXT))
            {
            foreach ($NEWS_TEXT as $key=>$value)
                {
                $item['title']=truncateText($value['title'], 40, "symbols", 0, 2);
                $item['full_url']=$value['full_url'];
                $item['date']=$value['date'];
                $rss_feeds_array[]=$item;
                }
            }
        }
    else
        {
        $item['title']="No announcements";
        $rss_feeds_array[]=$item;
        }

    return $rss_feeds_array;
    }


//display sidebar menu
function returnLeftMenuArray($script_filename)
    {
    $left_menu_array[]=array("title"=>"Products", "target"=>"#", "icon"=>"fa-shopping-cart", "sub_menu"=>array(array("title"=>"Add New Product", "target"=>"products_add.php"), array("title"=>"View Products", "target"=>"products_view.php", "target_sub"=>"products_edit.php")));
    $left_menu_array[]=array("title"=>"Clients", "target"=>"#", "icon"=>"fa-users", "sub_menu"=>array(array("title"=>"Add New Client", "target"=>"clients_add.php"), array("title"=>"View Clients", "target"=>"clients_view.php", "target_sub"=>"clients_edit.php")));
    $left_menu_array[]=array("title"=>"Licenses", "target"=>"#", "icon"=>"fa-id-card-o", "sub_menu"=>array(array("title"=>"Add New License", "target"=>"licenses_add.php"), array("title"=>"View Licenses", "target"=>"licenses_view.php", "target_sub"=>"licenses_edit.php")));
    $left_menu_array[]=array("title"=>"Installations", "target"=>"#", "icon"=>"fa-hdd-o", "sub_menu"=>array(array("title"=>"View Installations", "target"=>"installations_view.php", "target_sub"=>"installations_edit.php")));
    $left_menu_array[]=array("title"=>"Callbacks & Reports", "target"=>"#", "icon"=>"fa-line-chart", "sub_menu"=>array(array("title"=>"View Callbacks", "target"=>"callbacks_view.php"), array("title"=>"View License Reports", "target"=>"reports_license_view.php"), array("title"=>"View System Reports", "target"=>"reports_system_view.php")));
    $left_menu_array[]=array("title"=>"License Notifications", "target"=>"#", "icon"=>"fa-commenting-o", "sub_menu"=>array(array("title"=>"Customize Notifications", "target"=>"notifications_edit.php")));
    $left_menu_array[]=array("title"=>"Software Settings", "target"=>"#", "icon"=>"fa-cogs", "sub_menu"=>array(array("title"=>"General Settings", "target"=>"settings_general.php"), array("title"=>"Database Cleanup Settings", "target"=>"settings_cleanup.php")));
    $left_menu_array[]=array("title"=>"API Keys", "target"=>"#", "icon"=>"fa-key", "sub_menu"=>array(array("title"=>"Add New API Key", "target"=>"api_keys_add.php"), array("title"=>"View API Keys", "target"=>"api_keys_view.php", "target_sub"=>"api_keys_edit.php")));
    $left_menu_array[]=array("title"=>"Extra Tools", "target"=>"#", "icon"=>"fa-wrench", "sub_menu"=>array(array("title"=>"Configuration Generator", "target"=>"config_generate.php")));

    foreach ($left_menu_array as $left_menu_item)
        {
        $item['title']=$left_menu_item['title'];
        $item['class']="treeview";
        $item['target']=$left_menu_item['target'];
        $item['icon']=$left_menu_item['icon'];
        $item['sub_menu']= [];

        foreach ($left_menu_item['sub_menu'] as $left_menu_sub_item) //format submenus for each entry
            {
            $sub_item['title']=$left_menu_sub_item['title'];
            $sub_item['class']="";
            $sub_item['target']=$left_menu_sub_item['target'];

            if (!empty($left_menu_sub_item['target']) && $script_filename==$left_menu_sub_item['target'] || !empty($left_menu_sub_item['target_sub']) && $script_filename==$left_menu_sub_item['target_sub']) //mark this menu as active in sidebar
                {
                $item['class']="treeview active";
                $sub_item['class']="active";
                }

            $item['sub_menu'][]=$sub_item;
            }

        $sidebar_items_array[]=$item;
        }

    return $sidebar_items_array;
    }
