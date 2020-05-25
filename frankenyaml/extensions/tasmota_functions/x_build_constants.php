<?php
defined("IN_YAML_HELPER") || die("not in helper");



define('DB_FILENAME', TASMOTA_FUNCTIONS_DIRECTORY . "/tasmota_database.txt");

define("UNIVERSAL_BLANK", "this is a placeholder to signify that this is blank");

defined("IN_YAML_HELPER") || die("not in helper");
$device_username = getC("tasmota_login");
$device_password = getC("tasmota_password");
if ($device_username == UNIVERSAL_BLANK)
    $device_username = $device_password = "";

define('TASMOTA_LIST_KEY', "tasmota_list_default");
define("TASMOTA_IP_SCAN_LIST", "ip_scan");
define('LIST_DB_KEYS', array(TASMOTA_LIST_KEY => "Default List",
        TASMOTA_IP_SCAN_LIST => "List From Scanned"));
build_db_constant(false);

define('JAVASCRIPT_DUMP_ID_PREFIX', "tasmota_master");
define('JAVASCRIPT_DUMP_ID', JAVASCRIPT_DUMP_ID_PREFIX . "_td_js_output");
$split = explode("?", $_SERVER['REQUEST_URI']);
define("THIS_FILE", $split[0]);
if (!isset($exfpath))
    $exfpath = THIS_FILE;
define("JS_PATH", $exfpath);

define("SHORT_MODE_CMND", "STATUS 0");
define("FULL_MODE_CMND", "STATUS 0");
$list_display_mode = defined('LIST_DISPLAY_MODE') ? LIST_DISPLAY_MODE : false;

if ($list_display_mode == "full")
    $default_command = FULL_MODE_CMND;
elseif ($list_display_mode == "short")
    $default_command = SHORT_MODE_CMND;
else
    $default_command = "error: display mode not set, but you should not ever see this error";

define("DEFAULT_CMND", $default_command);
// the following is not necessary but will help with browsing
define("MQTT_HOSTS", array());


define("MAX_RELAYS", 12);
define("MAX_SWITCHES", 8); // this is always 8
define("RELAY_PLACEHOLDER",123456789)
?>