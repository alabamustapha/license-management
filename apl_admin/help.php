<?php
require_once("../apl_config.php");
require_once("../apl_ver.php");
require_once("../apl_settings.php");
require_once("login_check.php");


$page_title="Help Section";
$page_message="Read $PRODUCT_NAME documentation, see advanced usage tips for expert users, and find detailed answers to frequently asked questions.";
$page_message_class="alert alert-info";


//display help topics
function returnHelpTopicsArray($PRODUCT_NAME, $ROOT_URL, $array_to_use, $help_topic_class)
    {
    $root_array=array();

    //using application
    $using_application=array(
    "Getting started"=>"Before you can start protecting your script with $PRODUCT_NAME, it only takes 3 easy steps: adding product, adding client (optional), and adding license itself. For your convenience, all the steps are fully explained below.",

    "Adding new product"=>"The first and most obvious step to license a product is to create the product itself. Open the <i>Products > Add New Product</i> tab to create your product. Product name and SKU fields are mandatory and must be unique (different products with same name and/or SKU can't exist), while other fields are optional. The number of products is unlimited.",

    "Editing existing product"=>"All previously added products can be edited using the <i>Products > View Products</i> tab. Click desired product name to make any changes needed. Even after license for some product is issued and actively used, all type of information (even product name or SKU) can be modified without affecting existing licenses. Obviously, setting product status to Inactive will automatically terminate all licenses issued for selected product. For example, if there are 10 clients with active licenses for particular product, all 10 licenses will become inactive. This option should only be used if you want to stop all licensed clients from using specific product for some reason.",

    "Adding new client"=>"Once product is ready use, it's time to add a new client. Open the <i>Clients > Add New Client</i> tab to add a client who will use licensed product. All fields are mandatory. Since email address is used to identify client and verify his license, it must be unique (different clients with same email address can't exist). The number of clients is unlimited.<br><br>
    While it's always recommended to create client's profile before issuing a license, there might be a scenario when you don't know client's details (such as name and email) or want to issue anonymous license. In case that happens, you can skip this step and issue a license without using client's profile.",

    "Editing existing client"=>"Just like products, existing clients can also be edited using the <i>Clients > View Clients</i> tab. Click desired client name to make any changes needed. Client first and last name can be modified at any time, but modifying licensed email address (after license is issued and actively used) will cause the next license verification to fail. In order to continue using the old license (issued under old email address), client will need to update his licensed email address in installed script. Just as you would expect, setting client status to Inactive will automatically terminate all licenses issued for selected client. For example, if client has 5 separate licenses for 5 different products, all 5 licenses will become inactive.",

    "Adding new license"=>"Finally, it's time to issue a new license. Open the <i>Licenses > Add New License</i> tab to add a new license. When client's details are known, just select client and product from the list. In case you don't know client's name and email (so you can't create client's profile) or want to issue anonymous license, select product from the list and enter unique license code. This way, anyone who has this code (number) will be able to activate his copy of protected script (this is exactly how activation works for most of shareware products).<br><br>
    Only client (for personal licenses) or license code (for anonymous licenses) and product fields are mandatory, but optional fields can greatly improve overall protection of your application by binding license to specific IP address and/or domain, limiting the number of allowed installations, or even causing license to expire at some date. More on that below.<br><br>
    If IP address is entered, application will only work on specific IP. Once IP address gets changed, license check will fail. If domain is entered, application will only work on specific domain. Domain should always be entered as clientdomain.com or clientdomain.com/path (it should never include / symbol at the end). If licensed domain is entered as clientdomain.com, product will work on clientdomain.com and clientdomain.com/any/directory. If licensed domain is entered as clientdomain.com/path, product will only work on clientdomain.com/path. Sure enough, both IP and domain fields can be filled at the same time, which means application will only work if IP address matches domain (for example, if IP address 1.1.1.1 and domain clientdomain.com is set, but clientdomain.com actually points to 1.1.1.2, application will not work). It's also possible to add multiple licensed IPs and/or domains by separating them with comma (,) symbol.<br><br>
    Additionally, option to always require a domain can be activated; it will block all installation attempts that are made without uploading files on a real domain (it means installing application on http://localhost or http://1.1.1.1 will fail). This option greatly improves overall protection of your application.<br><br>
    If installations limit is set, client will not be able to perform more installations once limit is reached. For example, if current limit is two installations, third installation will only work when one of previous installations is deleted via the <i>View Installations</i> tab (the limit is set per license) If installations limit is set, IP address and/or domain fields can be left empty safely, since client will not be able to perform more installations anyway (no matter domain and/or IP he tries to install on).<br><br>
    If expiration date is set, application will stop working after this date (license expiration date can be renewed later). Additionally, it's possible to set updates expiration date. If updates expiration date is set, application will continue working as long as the license is active, but updates will not be available after this date.",

    "Editing existing license"=>"All issued licenses be edited using the <i>Licenses > View Licenses</i> tab. Click desired license to make any changes needed. If license is actively used, be aware that modifying existing license data might cause license verification to fail. For example, if software is installed on clientdomain.com and licensed domain is changed to clientdomain.com/path, existing installation will stop working. In other hand, license modification is a must when some details (such as client's IP address) gets changed, so client can continue using protected script.<br><br>
    For very obvious security reasons, licensed product or license owner can not be changed after license is issued. If existing client needs a license for another product, just terminate existing license (optional) and issue a new one. Setting license status to Inactive will automatically terminate this single license. For example, if client has 3 separate licenses for 3 different products, deactivating one license will prevent client from using selected product, but allow him using other two products he still has active licenses for.",

    "Editing existing installation"=>"All software installations be edited using the <i>Installations > View Installations</i> tab. Click desired installation to make any changes needed. For security reasons, only installation IP address can be modified. An existing installation should only be modified if client's IP address gets changed and license verification fails. Otherwise, no installation details should be changed in any case. Deleting existing installation will cause license verification to fail and client will need to completely re-install protected application.",

    "Customizing notifications"=>"$PRODUCT_NAME allows displaying custom notifications in your application for each event (failed license check, expired license, etc.), making it fully compatible with your language. Open the <i>License Notifications > Customize Notifications</i> tab to modify default notifications. The full list of supported variables is below (all variables are sorted alphabetically):<br><br>
    <table class=\"custom_table_minimal table table-bordered table-striped\">
            <thead>
              <tr>
                 <th>Variable Name</th>
                 <th>Variable Value</th>
                 <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                 <td>%CLIENT_EMAIL%</td>
                 <td>Client's email address</td>
                 <td></td>
              </tr>
              <tr>
                 <td>%IP_ADDRESS%</td>
                 <td>IP address of remote server</td>
                 <td></td>
              </tr>
              <tr>
                 <td>%LICENSE_CANCEL_DATE%</td>
                 <td>License cancellation date</td>
                 <td>Only available for cancelled licenses</td>
              </tr>
              <tr>
                 <td>%LICENSE_CODE%</td>
                 <td>License code</td>
                 <td>Only available if entered during installation</td>
              </tr>
              <tr>
                 <td>%LICENSE_EXPIRE_DATE%</td>
                 <td>License expiration date</td>
                 <td>Only available for time-expiring licenses</td>
              </tr>
              <tr>
                 <td>%LICENSE_LIMIT%</td>
                 <td>Maximum number of allowed installations</td>
                 <td>Only available for licenses with installations limit</td>
              </tr>
              <tr>
                 <td>%LICENSE_UPDATES_DATE%</td>
                 <td>Updates expiration date</td>
                 <td>Only available for updates-expiring licenses</td>
              </tr>
              <tr>
                 <td>%PRODUCT_ID% </td>
                 <td>Internal product ID</td>
                 <td>Not recommended to display for security reasons</td>
              </tr>
              <tr>
                 <td>%PRODUCT_TITLE% </td>
                 <td>Product name</td>
                 <td>Not available for 'Invalid License' notifications for security reasons</td>
              </tr>
              <tr>
                 <td>%PRODUCT_VERSION% </td>
                 <td>Current product version</td>
                 <td>Only available if entered during product creation/modification</td>
              </tr>
              </tr>
              <tr>
                 <td>%ROOT_URL%</td>
                 <td>URL where application is (being) installed</td>
                 <td></td>
              </tr>
            </tbody>
          </table>",
    );


    //using API
    $using_api=array(
    "Enabling and configuring API"=>"Using $PRODUCT_NAME API, you can perform any action without even opening administration dashboard. Just enable API and send all requests to <i>$ROOT_URL/apl_api/api.php</i>. API can be enabled using the <i>Software Settings > General Settings</i> tab at any time.<br><br>
    Once enabled, a new API key should be created using the <i>API Keys > Add New API Key</i> section. The software allows creating multiple API keys with different permissions; therefore, you can grant different permissions for different keys. Optionally, you can accept API requests only from IP address(es) listed in API IP field, so requests from other IPs will be blocked. Any API key can be activated/deactivated at any time.<br><br>
    Additionally, you can secure the /apl_api directory using <a href='http://www.htaccesstools.com/articles/password-protection/' target='_blank'>htaccess password protection</a> (you will need to add authorization headers to your API requests in this case). If you don't plan using API, it should be disabled.",

    "Formatting and sending API requests"=>"All API requests should be sent to /apl_api/api.php using <code>POST</code> method and include unique API secret (and additional data you want to submit, of course). Here's a basic example on how API request should look like:<br><br>
    API URL:<br><pre>$ROOT_URL/apl_api/api.php</pre><br>Post Data:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=FUNCTION_TO_CALL&var1=value1&var2=value2...</pre><br>
    API will always return a success/failure message for each request. In case some action fails, the cause of error will be included as well. If no message is returned at all, it means no data was sent to API. It usually happens when data is incorrectly submitted using GET method instead of POST.<br><br>
    Some API functions require a status and/or date to be included, which is easy to do: 1 means active (enabled), 0 means inactive (disabled). For example, if you add a product and set its status to 0, product will be stored in database, but it won't be available for licensing.<br><br>
    Active (enabled): <code>1</code><br>Inactive (disabled): <code>0</code><br><br>
    Date should always be formatted using a locale neutral format (as specified by ISO 8601): <code>yyyy-mm-dd</code>. Date only needs to be included when records are edited, and you can get correct date value using search API. Even if search API returned date formatted as <code>0000-00-00</code>, be sure to include it as it is.<br><br>
    In case any record needs to be deleted, an extra parameter <code>delete_record</code> with value <code>1</code> should be added:<br>
    Delete Record: <code>delete_record=1</code>",

    "API parameters List"=>"All the API parameters you will ever use are self-explanatory. However, if you find some variable not clear, refer to this list for more details (all variables are sorted alphabetically):<br><br>
    <table class=\"custom_table_minimal table table-bordered table-striped\">
            <thead>
              <tr>
                 <th>Parameter Name</th>
                 <th>Description</th>
                 <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                 <td>client_email</td>
                 <td>Client email address</td>
                 <td>Must be unique</td>
              </tr>
              <tr>
                 <td>client_fname</td>
                 <td>Client first name</td>
                 <td></td>
              </tr>
              <tr>
                 <td>client_id</td>
                 <td>Account ID</td>
                 <td>Can be parsed from search API</td>
              </tr>
              <tr>
                 <td>client_lname</td>
                 <td>Client last name</td>
                 <td></td>
              </tr>
              <tr>
                 <td>client_status</td>
                 <td>Client status</td>
                 <td><code>1</code> - active, <code>0</code> - inactive</td>
              </tr>
              </tr>
              <tr>
                 <td>delete_record</td>
                 <td>Delete selected record</td>
                 <td><code>1</code> - to delete</td>
              </tr>
              <tr>
                 <td>installation_ip</td>
                 <td>Installation IP address</td>
                 <td></td>
              </tr>
              <tr>
                 <td>license_code</td>
                 <td>License code</td>
                 <td>Only required for anonymous licenses</td>
              </tr>
              <tr>
                 <td>license_domain</td>
                 <td>Domain(s) to bind license to</td>
                 <td></td>
              </tr>
              <tr>
                 <td>license_expire_date</td>
                 <td>Date after which license will expire</td>
                 <td>Can't be earlier than today's date</td>
              </tr>
              <tr>
                 <td>license_id</td>
                 <td>License ID</td>
                 <td>Can be parsed from search API</td>
              </tr>
              <tr>
                 <td>license_ip</td>
                 <td>IP address(es) to bind license to</td>
                 <td></td>
              </tr>
              <tr>
                 <td>license_limit</td>
                 <td>Maximum number of installations</td>
                 <td></td>
              </tr>
              <tr>
                 <td>license_order_number</td>
                 <td>Order number of license purchase</td>
                 <td></td>
              </tr>
              <tr>
                 <td>license_require_domain</td>
                 <td>Option to always require a domain</td>
                 <td><code>1</code> - yes, <code>0</code> - no</td>
              </tr>
              <tr>
                 <td>license_status</td>
                 <td>License status</td>
                 <td><code>1</code> - active, <code>0</code> - inactive</td>
              </tr>
              <tr>
                 <td>license_updates_date</td>
                 <td>Date after which updates will not be available</td>
                 <td>Can't be earlier than today's date</td>
              </tr>
              <tr>
                 <td>product_description</td>
                 <td>Short product description</td>
                 <td></td>
              </tr>
              <tr>
                 <td>product_id</td>
                 <td>Product ID</td>
                 <td>Can be parsed from search API</td>
              </tr>
              <tr>
                 <td>product_sku</td>
                 <td>Product stock keeping unit</td>
                 <td>Must be unique</td>
              </tr>
              <tr>
                 <td>product_status</td>
                 <td>Product status</td>
                 <td><code>1</code> - active, <code>0</code> - inactive</td>
              </tr>
              <tr>
                 <td>product_title</td>
                 <td>Product name</td>
                 <td>Must be unique</td>
              </tr>
              <tr>
                 <td>product_url_download</td>
                 <td>Product download URL</td>
                 <td></td>
              </tr>
              <tr>
                 <td>product_url_homepage</td>
                 <td>Product homepage URL</td>
                 <td></td>
              </tr>
              <tr>
                 <td>product_version</td>
                 <td>Product version</td>
                 <td></td>
              </tr>
              <tr>
                 <td>search_keyword</td>
                 <td>Keyword(s) to be used for search</td>
                 <td></td>
              </tr>
              <tr>
                 <td>search_type</td>
                 <td>API search type</td>
                 <td>Possible values: product, client, license</td>
              </tr>
            </tbody>
          </table>",

    "Adding new product"=>"API Function: <code>products_add</code><br>Required Parameters: <code>product_title, product_sku, product_status</code><br>Optional Parameters: <code>product_description, product_url_homepage, product_url_download, product_version</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=products_add&product_title=My_Product_Name&product_sku=MPN&product_status=1&product_description=something&product_url_homepage=$ROOT_URL&product_url_download=$ROOT_URL/file.zip&product_version=1.0</pre>",

    "Editing existing product"=>"API Function: <code>products_edit</code><br>Required Parameters: <code>product_id, product_title, product_sku, product_status</code><br>Optional Parameters: <code>product_description, product_url_homepage, product_url_download, product_version, delete_record</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=products_edit&product_id=1&product_title=My_Product_Name&product_sku=MPN&product_status=1&product_description=something&product_url_homepage=$ROOT_URL&product_url_download=$ROOT_URL/file.zip&product_version=1.0</pre>",

    "Adding new client"=>"API Function: <code>clients_add</code><br>Required Parameters: <code>client_fname, client_lname, client_email, client_status</code><br>Optional Parameters: <code>N/A</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=clients_add&client_fname=First_Name&client_lname=Last_Name&client_email=client@email.com&client_status=1</pre>",

    "Editing existing client"=>"API Function: <code>clients_edit</code><br>Required Parameters: <code>client_id, client_fname, client_lname, client_email, client_status</code><br>Optional Parameters: <code>delete_record</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=clients_edit&client_id=1&client_fname=First_Name&client_lname=Last_Name&client_email=client@email.com&client_status=1</pre>",

    "Adding new license"=>"This one is a bit more tricky, since it requires submitting both product and client IDs stored in the database. How do you get required IDs? Using search API! Since a detailed description on how to use search API is available in the Help Section itself, let's assume you already know how to use it to find data needed.<br><br>
    API Function: <code>licenses_add</code><br>Required Parameters (personal license): <code>product_id, client_id, license_require_domain, license_status</code><br>Optional Parameters (personal license): <code>license_order_number, license_ip, license_domain, license_limit, license_expire_date, license_updates_date</code><br><br>
    Post Data Example (personal license):<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=licenses_add&product_id=1&client_id=1&license_require_domain=1&license_status=1&license_order_number=order123&license_ip=192.168.1.1&license_domain=clientdomain.com&license_limit=3&license_expire_date=".date("Y-m-d", strtotime("+1 year"))."&license_updates_date=".date("Y-m-d", strtotime("+6 months"))."</pre><br><br>
    Required Parameters (anonymous license): <code>product_id, client_id, license_code, license_require_domain, license_status</code><br>Optional Parameters (anonymous license): <code>license_order_number, license_ip, license_domain, license_limit, license_expire_date, license_updates_date</code><br><br>
    Post Data Example (anonymous license):<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=licenses_add&product_id=1&client_id=0&license_code=code123456&license_require_domain=1&license_status=1&license_order_number=order123&license_ip=192.168.1.1&license_domain=clientdomain.com&license_limit=3&license_expire_date=".date("Y-m-d", strtotime("+1 year"))."&license_updates_date=".date("Y-m-d", strtotime("+6 months"))."</pre><br><code>client_id</code> is always <code>0</code> for anonymous licenses.",

    "Editing existing license"=>"API Function: <code>licenses_edit</code><br>Required Parameters (personal license): <code>product_id, client_id, license_id, license_require_domain, license_status</code><br>Optional Parameters (personal license): <code>license_order_number, license_ip, license_domain, license_limit, license_expire_date, license_updates_date, delete_record</code><br><br>
    Post Data Example (personal license):<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=licenses_edit&product_id=1&client_id=1&license_id=1&license_require_domain=1&license_status=1&license_order_number=order123&license_ip=192.168.1.1&license_domain=clientdomain.com&license_limit=3&license_expire_date=".date("Y-m-d", strtotime("+1 year"))."&license_updates_date=".date("Y-m-d", strtotime("+6 months"))."</pre><br><br>
    Required Parameters (anonymous license): <code>product_id, client_id, license_id, license_code, license_require_domain, license_status</code><br>Optional Parameters (anonymous license): <code>license_order_number, license_ip, license_domain, license_limit, license_expire_date, license_updates_date, delete_record</code><br><br>
    Post Data Example (anonymous license):<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=licenses_edit&product_id=1&client_id=1&license_id=1&license_code=code123456&license_require_domain=1&license_status=1&license_order_number=order123&license_ip=192.168.1.1&license_domain=clientdomain.com&license_limit=3&license_expire_date=".date("Y-m-d", strtotime("+1 year"))."&license_updates_date=".date("Y-m-d", strtotime("+6 months"))."</pre><br><code>client_id</code> is always <code>0</code> for anonymous licenses.",

    "Editing existing installation"=>"API Function: <code>installations_edit</code><br>Required Parameters: <code>installation_id, installation_ip</code><br>Optional Parameters: <code>delete_record</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=installations_edit&installation_id=1&installation_ip=192.168.1.1</pre>",

    "Searching for data"=>"API Function: <code>search</code><br>Required Parameters: <code>search_type, search_keyword</code><br>Optional Parameters: <code>N/A</code><br><br>
    Post Data Example:<br><pre>api_key_secret=UNIQUE_API_SECRET&api_function=search&search_type=product&search_keyword=My_Product_Name</pre><br>Parameter <code>search_type</code> can use of these 3 values: <code>product, client, license</code>. Parameter <code>search_keyword</code> must contain exact word(s) to be found. Products are found by their names, clients are found by their email addresses, and licenses are found by licensed email addresses or license codes. Put simply, if you need to find a product, use keyword <code>My_Product_Name</code>. If you need to find a client, use keyword <code>client@email.com</code>. If you need to find a license, use keyword <code>client@email.com</code> or <code>code123456</code>.<br><br>
    API will return all records that match search keyword, wrapping each record into appropriate tags: <pre>".htmlspecialchars("<record>RETURNED_DATA</record>", ENT_QUOTES, "UTF-8")."</pre><br>Each record will contain its own unique tags, that depend on search type. For example, if you search for a product, this is how search results may look like: <pre>".htmlspecialchars("<record><product_id>1</product_id><product_title>My_Product_Name</product_title><product_description>something</product_description><product_sku>MP</product_sku><product_url_homepage>$ROOT_URL</product_url_homepage><product_url_download>$ROOT_URL/file.zip</product_url_download><product_date>".date("Y-m-d")."</product_date><product_version>1.0</product_version><product_status>1</product_status></record>", ENT_QUOTES, "UTF-8")."</pre><br>If you search for a client, search result may look like: <pre>".htmlspecialchars("<record><client_id>1</client_id><client_fname>First_Name</client_fname><client_lname>Last_Name</client_lname><client_email>client@email.com</client_email><client_active_date>".date("Y-m-d")."</client_active_date><client_cancel_date>0000-00-00</client_cancel_date><client_status>1</client_status></record>", ENT_QUOTES, "UTF-8")."</pre><br>If you search for a license, search result may look like: <pre>".htmlspecialchars("<record><license_id>1</license_id><client_id>1</client_id><product_id>1</product_id><license_code>code123456</license_code><license_order_number>order123</license_order_number><license_ip>192.168.1.1</license_ip><license_domain>clientdomain.com</license_domain><license_require_domain>1</license_require_domain><license_limit>3</license_limit><license_active_date>".date("Y-m-d")."</license_active_date><license_cancel_date>0000-00-00</license_cancel_date><license_expire_date>".date("Y-m-d", strtotime("+1 year"))."</license_expire_date><license_updates_date>".date("Y-m-d", strtotime("+6 months"))."</license_updates_date><license_comments></license_comments><license_status>1</license_status><product_title>My_Product_Name</product_title><product_description>something</product_description><product_sku>MP</product_sku><product_url_homepage>$ROOT_URL</product_url_homepage><product_url_download>$ROOT_URL/file.zip</product_url_download><product_date>".date("Y-m-d")."</product_date><product_version>1.0</product_version><product_status>1</product_status><client_fname>First_Name</client_fname><client_lname>Last_Name</client_lname><client_email>client@email.com</client_email><client_active_date>".date("Y-m-d")."</client_active_date><client_cancel_date>0000-00-00</client_cancel_date><client_status>1</client_status></record>", ENT_QUOTES, "UTF-8")."</pre>",

    "Ready to use code for connecting to API"=>"If you don't know how to make POST requests to remote server, you can use this code to connect to API:<br><br>
    <pre>function simplePost(\$post_url, \$post_info)
    {
    \$ch=curl_init();
    curl_setopt(\$ch, CURLOPT_URL, \$post_url);
    curl_setopt(\$ch, CURLOPT_POST, 1);
    curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$post_info);
    curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, 1);
    \$result=curl_exec(\$ch);
    curl_close(\$ch);

    return \$result;
    }</pre><br>Should you want to add new product, just use this code:<br><br>
    <pre>simplePost(\"$ROOT_URL/apl_api/api.php\", \"api_key_secret=UNIQUE_API_SECRET&api_function=products_add&product_title=My_Product_Name&product_sku=MPN&product_status=1\")</pre>",
    );



    //protecting your script
    $protecting_your_script=array(
    "General Requirements"=>"$PRODUCT_NAME (and all its modules) will work on any server that supports <a href=\"http://php.net/\" target=\"_blank\">PHP</a> 5.3 or higher along with its most popular extensions: <a href=\"http://php.net/manual/en/book.mysqli.php\" target=\"_blank\">MySQLi</a> (with <a href=\"http://php.net/manual/en/mysqli.quickstart.prepared-statements.php\" target=\"_blank\">Prepared Statements</a>), <a href=\"http://php.net/manual/en/book.curl.php\" target=\"_blank\">cURL</a> and <a href=\"http://php.net/manual/en/book.mcrypt.php\" target=\"_blank\">Mcrypt</a>. 99% of hosting providers (even free ones) have these extensions enabled by default; therefore, your application will be virtually compatible with any server around.<br><br>
    End user (user, who will run your script protected by $PRODUCT_NAME) server has no extra requirements; it only needs to support the same PHP extensions as $PRODUCT_NAME server. If user server doesn't support MySQLi (which would be very rare), the application can still work at maximum performance as long as cURL and Mcrypt are installed.",

    "Integrating $PRODUCT_NAME into your script"=>"This type of information is only available outside online administration interface for security reasons. A detailed integration guide is included in the $PRODUCT_NAME installation package; see 'DOCUMENTATION' directory for step-by-step instructions and full documentation. The installation package also contains a fully working demo script protected by $PRODUCT_NAME; feel free to explore its source code for even better understanding how everything works.",
    );



    //faq & troubleshooting
    $faq_troubleshooting=array(
    "What is additional authentication protection in admin profile for?"=>"This option additionally secures your authentication by constantly checking your IP address and browser details. Technically speaking, if someone manages to steal authentication cookie from your computer and loads it into his browser to access your profile, login will fail. However, be aware that login will also fail if your own IP address or any bit of browser details (like version number) gets changed. But don't worry - all you need to do is login again, so new authentication credentials are created for you.",

    "What's the difference between personal and anonymous license?"=>"Personal licenses are issued when basic client's details (such as name and email) are known, so client's profile can be created and stored in the database. In order to activate product, client will only need to enter his licensed email address during installation; therefore, this method is always recommended for easier licenses management.<br><br>
    However, there might be some scenarios when you want to issue a license to anyone who knows activation code (for example, when you sell license keys without asking for personal buyer's information). This is called an anonymous license and doesn't require a client's profile to be created. Instead of this, you simply generate a unique license code when issuing a new license. Once someone enters this code during installation, product gets activated. Needless to say, the code must match licensed product - if code is generated for product A, it will not work with any other product.<br><br>
    No matter which method you choose, all the additional licensing options work equally.",

    "Can both email address and license code based license be issued?"=>"License can only be either personal (client-based) or anonymous (code-based), which means issuing both email address and code based license is not possible.<br><br>However, it's perfectly fine to ask client to enter his email address during installation when anonymous license is issued. It's the default way $PRODUCT_NAME itself works: email address is used to login to the administration dashboard, and code is used to verify license (don't worry, $PRODUCT_NAME will automatically determine if license is personal or anonymous).",

    "What are limitations for license code?"=>"None. You are welcome to use any format for license code to suit your personal needs. You can even use order number as license code (so client can activate his script by entering his order ID). The only requirement for code is to be unique for each license. For your convenience, $PRODUCT_NAME will return duplicated data error if some code already exists in the database.",

    "What's the difference between license and updates expiration?"=>"License expiration is used to set a date after which your script will stop working and license will need to be renewed. This is very useful for developers who want users to renew their licenses after X days/weeks/months/years in order to continue using product.<br><br>
    Updates expiration is used to set a period for which your users can receive script updates. This way, developers can issue lifetime licenses (script will never stop working), but ask users to pay for updates after X days/weeks/months/years since original purchase. For example, if your script has updates section (used to check for and/or download new versions), $PRODUCT_NAME will allow users to receive updates for a specific time period only. Once the limit is reached, users will see a message asking to renew their contract in order to continue receiving new versions (or they can keep using old version).<br><br>
    Needless to say, $PRODUCT_NAME allows setting both dates independently from each other.",

    "Can multiple IPs and/or domains be assigned to the same license?"=>"Yes, a single license can have an unlimited number of allowed IPs and/or domains. Just separate each authorized IP/domain with comma (,) symbol and you are done.",

    "How installations limit works?"=>"Limiting the number of total installations is the easiest way to add maximum protection to your script without a need to bind license to IP address and/or domain. For example, if you don't want end user to share his copy of script with 3rd parties, but you don't know what IP and/or domain user will install script on, simply set installations limit to 1. This way, user will be able to install script on any IP/domain, but if he tries to install another copy, new installation will be blocked.<br><br>
    If user decides he wants to have script running on another IP/domain later, old installation needs to be deleted by you using the <i>Installations > View Installations</i> tab, so the license becomes \"available\" and another installation can be performed again. If user wants to re-install script on the same IP/domain, he can do so at any time; if the same old system is used, $PRODUCT_NAME allows an unlimited number of re-installations on the same computer. However, if the same computer is used, but IP address and/or domain is changed, re-installation will be counted as a new installation just as you would expect from a sophisticated protection algorithm.<br><br>
    The limit can also be modified after some installations were performed. For example, if user performed 3 installations, and limit is set to 1 later, all installations will stop working until 2 installations are removed using the <i>Installations > View Installations</i> tab.",

    "License can't be issued because of invalid license type"=>"If error message 'Invalid license type (personal license can't use license code)' is displayed when issuing new license, it means personal and anonymous licenses are mixed together. In other words, if client's profile is selected from the list (personal license needs to be issued), license code should be empty because it's only used to verify anonymous licenses. Alternatively, if license code is entered (anonymous license needs to be issued), client's profile should not be selected.",

    "License can't be issued because of invalid domain"=>"If error message 'Invalid domain' is displayed when issuing new license, most likely licensed domain is entered wrong way. For example, it can't be IP-based (like http://1.1.1.1), include scheme (like http://www.clientdomain.com), or slash ( / ) symbol at the end (like http://clientdomain.com/path). In other words, it should be entered like clientdomain.com or clientdomain.com/path.",

    "Domain is already in use by another client"=>"In order to prevent any possible piracy, installation domain is locked to person who installed protected script on specific domain first. For example, if client A deploys a script protected by $PRODUCT_NAME on domain.com, client B will see error message when trying to install his copy of script on the same domain (because it's natural that client B should never have access to client's A domain).<br><br>
    However, in some very rare circumstances, there might be a situation when client B needs to replace existing installation on client's A domain with his own script. In case that happens, simply delete old installation using the <i>Licenses > View Licenses</i> tab, so client B can deploy his copy of script on \"locked\" domain.",

    "Maximum number of installations reached"=>"Once the maximum number of allowed installations for individual license is reached, new installations are blocked. If client needs to perform more more installations, the limit can be increased using the <i>Licenses > View Licenses</i> tab, or one of old installations should be deleted using the same tab. Deleting installation record in database is enough; even if user doesn't remove old files from his system, old installation will stop working within next automated license verification.",

    "Client's IP address was changed and license verification failed"=>"Each installation is locked to a specific IP address used to perform this installation. Once IP address gets changed, license verification fails (so even if someone clones all the protected files and databases, this illegal copy will not work). However, there might be a scenario when client's IP gets changed. In case that happens, simply update installation IP address using the <i>Installations > View Installations</i> tab, so blocked installation continues working.",

    "Record can't be added or updated"=>"If error message 'Invalid record details or duplicated record (no new data)' is displayed when adding new (or updating existing) record, it means this record is already stored in database. For example, it may happen when adding a new product that uses existing SKU of other product, adding a new client with email address already used by another client, adding a new license of the same product to the same client again, and so on. In other words, this error prevents you from adding duplicated data to you database; therefore, once it's displayed, double-check record details and fix them accordingly.",

    "Some licenses don't display client's data"=>"Since $PRODUCT_NAME allows issuing anonymous licenses, it's perfectly normal that no client's data is displayed for this type of licenses (because there isn't any). However, anonymous licenses always contain unique license code in client's field.",

    "License can't be installed or verified because of invalid signature"=>"The most common issue of invalid signature is inaccurate time on your server (where $PRODUCT_NAME is installed) and/or inaccurate time on user's machine (where your script is installed). It doesn't matter which timezone is used, but operating system time must always be accurate. For example, if actual time in your timezone is ".date("Y-m-d H:i").", but computer has wrong time set to ".date("Y-m-d H:i", strtotime("+1 hour")).", license signature might be identified as invalid; therefore, make sure system time is accurate.<br><br>The same error also might occur when license code is entered along with licensed email during installation. Since license can be either personal (email-based) or anonymous (code-based), license code should always be empty for personal licenses.",

    "No callbacks are received"=>"If no license verification callbacks from specific installation (license) are displayed in reports section, there might be a few different reasons. For example, it's possible user simply deleted script from his server. Another very common reason: script isn't used at the moment. Script performs initial verifications during installation and first use, but what happens later?<br><br>
    Let's say script is configured to perform license verification every 7 days, and gets installed on ".date("Y-m-d").". Naturally, another verification will be performed on ".date("Y-m-d", strtotime("+7 days")).". However, if user doesn't launch script after ".date("Y-m-d", strtotime("+6 days")).", no callback will be made. In other words, even if script is installed (bot not used) for months, no callbacks will be available to reduce system resources usage. However, license verification will be triggered the same second script gets used again (even if user does so years later).",

    "License is disabled, but script still works"=>"In order to reduce resources usage on your server, automated license verification is performed every X days/weeks/months (as defined by you inside script configuration). For example, if script is configured to verify license every 7 days, last license verification was performed on ".date("Y-m-d", strtotime("-1 day"))." and license was deactivated on ".date("Y-m-d").", script will automatically stop working within next license check on ".date("Y-m-d", strtotime("+6 days")).".",

    "Not all files are deleted when invalid license is detected"=>"If $PRODUCT_NAME is configured to automatically delete all files and MySQL data when illegal license or possible cracking attempt is detected, but some files still remain, it means there's a problem with your files structure. According to documentation, directory <code>/SCRIPT</code> (can be renamed into anything you like) should always be placed in the root directory of your application. Put simply, if your application is installed at <code>/your/application/here</code>, $PRODUCT_NAME files should be located at <code>/your/application/here/SCRIPT</code> for auto-delete function to work properly.",
    );


    $collapse_no=1;
    foreach ($$array_to_use as $key=>$value)
        {
        $item_array['help_topic_title']=$key;
        $item_array['help_topic_text']=$value;
        $item_array['help_topic_class']="panel box box-$help_topic_class";
        $item_array['data_parent']="$array_to_use";
        $item_array['collapse_id']="collapse_$array_to_use"."$collapse_no";

        $collapse_no++;

        $root_array[]=$item_array;
        }

    return $root_array;
    }


$help_using_application_array=returnHelpTopicsArray($PRODUCT_NAME, $ROOT_URL, "using_application", "success");
$help_using_api_array=returnHelpTopicsArray($PRODUCT_NAME, $ROOT_URL, "using_api", "warning");
$help_protecting_your_script_array=returnHelpTopicsArray($PRODUCT_NAME, $ROOT_URL, "protecting_your_script", "danger");
$help_faq_troubleshooting_array=returnHelpTopicsArray($PRODUCT_NAME, $ROOT_URL, "faq_troubleshooting", "info");


//Twig templating starts
if (!isset($script_filename)) {$script_filename=basename($_SERVER['SCRIPT_FILENAME']);} //if $script_filename is not set yet (usually set in login_check.php), get it now (will be used in Twig forms)

Twig_Autoloader::register();
$loader=new Twig_Loader_Filesystem("../apl_templates"); //load files from templates directory
$twig=new Twig_Environment($loader); //create Twig environment

echo $twig->render(basename(__DIR__)."/".basename(__FILE__, ".php").".twig", get_defined_vars()); //render requested template file
//Twig templating ends
