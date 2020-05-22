<?php
// this file needs to be included LAST so start it with the letter z

defined("IN_YAML_HELPER") || die("not in helper");

$display_values_vertical = array(
    "MqttHost",
    "MqttRetry",
    "SetOption19",
    "SetOption30",
    "PowerOnState",
    "PowerRetain",
    "ButtonRetain",
    "SwitchRetain",
    "last_refresh");

define("COMMAND_BUTTONS", array(
    "cm:STATUS 5" => "Net Status*",
    "cm:STATUS 6" => "Mqtt Status",
    "cm:mqttretry" => "MQTTRetry?",
    "cm:status 0" => "All Status**",
    "cm:setoption19" => "AutoDisco?",
    "cm:setoption30" => "IsLight?",
    "cm:AP 1" => "AP 1",
    "cm:AP 2" => "AP 2",
    "cm:setoption13 1" => "ButtFix",
    "cm:setoption19 1" => "AD ON",
    "cm:setoption19 0" => "AD OFF",
    "cm:setoption30 1" => "IsLight",
    "cm:mqtthost 192.168.1.42" => "MQTTIP",
    "cm:mqttuser tasmota" => "MQTTUser",
    "cm:mqttretry 300" => "MQTTRetry300",
    "cm:mqttretry 10" => "MQTTRetry10",
    "cm:switchretain 0" => "Fix1",
    "cm:buttonretain 1" => "Fix2",
    "cm:buttonretain 0" => "Fix3",
    "cm:poweronstate 3" => "Fix4",
    "cm:powerretain 1" => "Fix5"));


define('DB_FILENAME', TASMOTA_FUNCTIONS_DIRECTORY . "/tasmota_database.txt");

define("UNIVERSAL_BLANK", "this is a placeholder to signify that this is blank");

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

define("SHORT_MODE_CMND", "STATUS 5");
define("FULL_MODE_CMND", "STATUS 0");
$list_display_mode = defined('LIST_DISPLAY_MODE')?LIST_DISPLAY_MODE:false;
if ($list_display_mode == "full")
    $default_command = "STATUS 0";
elseif($list_display_mode == "short")
    $default_command = "STATUS 5";
else
    $default_command = "error: display mode not set";
define("DEFAULT_CMND",$default_command);
// the following is not necessary but will help with browsing
define("MQTT_HOSTS", array());


define("MAX_RELAYS", 12);
define("MAX_SWITCHES", 8); // this is always 8
$shared_JSON = array(
    "Topic" => "Topic",
    "MqttRetry" => "MqttRetry",
    "StatusMQT" => array("MqttHost" => "MqttHost", "MqttUser" => "MqttUser"),
    "MqttUser" => "MqttUser",
    "MqttHost" => "MqttHost",
    "SetOption19" => "SetOption19",
    "SetOption30" => "SetOption30",
    "Status" => array(
        "Topic" => "Topic",
        "PowerOnState" => "PowerOnState",
        "PowerRetain" => "PowerRetain",
        "ButtonRetain" => "ButtonRetain",
        "SwitchRetain" => "SwitchRetain",
        "FriendlyName" => "RelayName"));

$stored_JSON = array("StatusNET" => array("Hostname" => "hostname", "IPAddress" =>
            "ip"));

$display_JSON = array("StatusNET" => array("Hostname" => "hostname", "IPAddress" =>
            "ip_address"));


$relay_JSON_prefix = array("POWER" => "State", "PulseTime" => "PulseTime");

$relay_commands = array(
    "PulseTime[num] 0" => "Clear PT",
    "POWER[num] 0" => "OFF",
    "POWER[num] 1" => "ON");

define('RELAY_COMMANDS', $relay_commands);
if ($list_display_mode == "full")
    $display_values = array(
        "select",
        "hostname",
        "ip" => "ip_address",
        "Topic",
        "other");
else
    $display_values = array(
        "select",
        "hostname",
        "ip" => "ip_address",
        "other");

// DO NOT ADD TO THIS: BAD THINGS
$display_values_vertical_relays = array("RelayName");

$vertical_result_height = 42;
$vertical_result_big_height = 220;

define("JSON_FOR_RELAYS", $relay_JSON_prefix);
for ($i = 0; $i < MAX_RELAYS; $i++) {
    foreach (JSON_FOR_RELAYS as $catch => $destination) {
        $catch_alt = $catch . ($i + 1);
        $catch_num = $catch . $i;
        $destination .= ($i + 1);

        $shared_JSON["$catch_alt"] = "$destination";
        $shared_JSON["Status"]["$catch_num"] = "$destination";
        if (!$i)
            $shared_JSON["$catch"] = "$destination";
    }
}

$switch_JSON_prefix = array("SwitchMode" => "SwitchMode");
define("JSON_FOR_SWITCHES", $switch_JSON_prefix);

for ($i = 0; $i < MAX_SWITCHES; $i++) {
    foreach (JSON_FOR_SWITCHES as $catch => $destination) {
        $catch_alt = $catch . ($i + 1);
        $catch_num = $catch . $i;
        $destination .= ($i + 1);

        $shared_JSON["Status"][$catch][$i] = "$destination";
        $shared_JSON["$catch_alt"] = "$destination";
        if (!$i)
            $shared_JSON["$catch"] = "$destination";
    }

}


define("DISPLAY_VALUES", $display_values);
define("DISPLAY_VALUES_VERTICAL", $display_values_vertical);

// just relay really
define("DISPLAY_VERTICAL_RELAYS", $display_values_vertical_relays);


define("TABLE_COLSPAN", count(DISPLAY_VALUES) * 2);

$base_column_width = 150;

$scrollbar_width = 22;
$checkbox_width = 24;
$horizontal_result_height = "60";

$horizontal_result_colspan = 2;

$js_output_colspan = TABLE_COLSPAN - ($horizontal_result_colspan * 3);
$js_output_rowspan = count(DISPLAY_VALUES_VERTICAL);
$horizontal_result_width = $base_column_width * $horizontal_result_colspan;

$js_output_width = $js_output_colspan * $base_column_width;
$vertical_result_width = floor(.66 * $horizontal_result_width);
$vertical_caption_width = floor(.33 * $horizontal_result_width);
$vertical_relays_width = $horizontal_result_width;
$button_area_width = $horizontal_result_width;
$main_table_width = $horizontal_result_width * count(DISPLAY_VALUES);


$vertical_result_height = $horizontal_result_height;
$vertical_caption_height = $vertical_result_height;
$vertical_relays_height = ($horizontal_result_height * (count(DISPLAY_VALUES_VERTICAL))) /
    count(DISPLAY_VERTICAL_RELAYS);


$js_output_height = $vertical_caption_height * (count(DISPLAY_VALUES_VERTICAL));


$tasmota_relay_table_width = $horizontal_result_width - $scrollbar_width;
$tasmota_relay_td_width = $tasmota_relay_table_width; //$tasmota_relay_table_width*.4;
$tasmota_relay_td_result_width = $tasmota_relay_table_width; //$tasmota_relay_table_width-$tasmota_relay_td_width;

$button_area_height = $js_output_height;
$bottom_row_height = $horizontal_result_height * 7;

$small_hr_td_colspan = 1;
// changing this does nothing but ruin everything.
$bottom_small_hr_tds_count = 2;
$bottom_title_td_colspan = 1;


$tiny_result_width = $horizontal_result_width - $checkbox_width;
$tiny_result_height = $horizontal_result_height;

$a = $bottom_manual_command_colspan = 2;
$a += $bottom_select_colspan = 1;
$a += $small_hr_td_colspan * $bottom_small_hr_tds_count;
$a += $bottom_title_td_colspan;
$bottom_hr_td_colspan = TABLE_COLSPAN - $a;

$a = $tasmota_feed_colspan = 2;
$a += $bottom_vertical_button_colspan = 1;
$a += $bottom_vertical_button_no_caption_colspan = 2;

$bottom_button_area_colspan = TABLE_COLSPAN - $a;
$bottom_button_area_width = $bottom_button_area_colspan * $base_column_width;
$bottom_vertical_button_width = $bottom_vertical_button_colspan * $base_column_width;
$bottom_title_td_width = $bottom_title_td_colspan * $base_column_width;
$small_hr_td_width = $small_hr_td_colspan * $base_column_width;

$bottom_manual_command_width = $bottom_manual_command_colspan * $base_column_width;
$bottom_hr_td_width = $bottom_hr_td_colspan * $base_column_width;
$bottom_hr_width = $bottom_hr_td_width - 20;
$bottom_select_td_width = $bottom_select_colspan * $base_column_width;


define("JSON_TO_STORED", array_merge($shared_JSON, $stored_JSON));


$display_JSON = array_merge($shared_JSON, $display_JSON);
append_references_to_display($display_JSON);
//var_dump($display_JSON);
define("JSON_TO_DISPLAY", $display_JSON);


$attrib = array(
    "valign=" => 'top',
    'rowspan' => $js_output_rowspan,
    'class' => "tasmota_main_result",
    'colspan' => $js_output_colspan);
$attrib['style']['min-width'] = $js_output_width . "px";
$attrib['style']['max-width'] = $js_output_width . "px";
$attrib['style']['width'] = $js_output_width . "px";
$attrib['style']['min-height'] = $js_output_height . "px";
$attrib['style']['max-height'] = $js_output_height . "px";
$attrib['style']['height'] = $js_output_height . "px";
define("JS_OUTPUT_ATTRIBUTES", $attrib);


$attrib = array("class" => "tasmota_vertical_caption");
$attrib['style']['min-width'] = $vertical_caption_width . "px";
$attrib['style']['max-width'] = $vertical_caption_width . "px";
$attrib['style']['width'] = $vertical_caption_width . "px";
$attrib['style']['min-height'] = $vertical_caption_height . "px";
$attrib['style']['max-height'] = $vertical_caption_height . "px";
$attrib['style']['height'] = $vertical_caption_height . "px";
define("DISPLAY_VALUES_VERTICAL_CAPTION_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = 'tasmota_vertical_result';
$attrib['style']['min-width'] = $vertical_result_width . "px";
$attrib['style']['max-width'] = $vertical_result_width . "px";
$attrib['style']['width'] = $vertical_result_width . "px";
$attrib['style']['max-height'] = $vertical_result_height . "px";
$attrib['style']['height'] = $vertical_result_height . "px";
define("DISPLAY_VALUES_VERTICAL_ATTRIBUTES_VALUE", $attrib);


$attrib = array();
$attrib["rowspan"] = $js_output_rowspan;
$attrib["class"] = "tasmota_relays_td";
$attrib['colspan'] = 2;
$attrib['style']['min-width'] = $vertical_relays_width . "px";
$attrib['style']['max-width'] = $vertical_relays_width . "px";
$attrib['style']['width'] = $vertical_relays_width . "px";
$attrib['style']['min-height'] = $vertical_relays_height . "px";
$attrib['style']['max-height'] = $vertical_relays_height . "px";
$attrib['style']['height'] = $vertical_relays_height . "px";
define("DISPLAY_VERTICAL_RELAYS_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_relay_table";
$attrib['style']['min-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['width'] = $tasmota_relay_table_width . "px";
define("TASMOTA_RELAY_TABLE_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = 1;
$attrib['style']['min-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['width'] = $tasmota_relay_table_width . "px";
define("TASMOTA_RELAY_TABLE_TH_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['colspan'] = 1;
$attrib['style']['min-width'] = $tasmota_relay_td_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_td_width . "px";
$attrib['style']['width'] = $tasmota_relay_td_width . "px";
$attrib['class'] = "tasmota_relay_table_buttons_td";
define("TASMOTA_RELAY_TABLE_TD_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['colspan'] = 1;
$attrib['style']['min-width'] = $tasmota_relay_td_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_td_width . "px";
$attrib['style']['width'] = $tasmota_relay_td_width . "px";
$attrib['class'] = "tasmota_relay_table_index_td";
define("TASMOTA_RELAY_TABLE_TD_INDEX_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = 1;
$attrib['class'] = "tasmota_relay_result_td animated_result_container start";
$attrib['style']['min-width'] = $tasmota_relay_td_result_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_td_result_width . "px";
$attrib['style']['width'] = $tasmota_relay_td_result_width . "px";
define("TASMOTA_RELAY_TABLE_TD_RESULT_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_relay_table";
$attrib['style']['min-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['width'] = $tasmota_relay_table_width . "px";
define("TASMOTA_RELAY_TABLE_SELECT_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = 1;
$attrib['style']['min-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['width'] = $tasmota_relay_table_width . "px";
$attrib['class'] = "tasmota_relay_table_buttons_td";
define("TASMOTA_RELAY_TABLE_SELECT_TD_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = 1;
$attrib['style']['min-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['max-width'] = $tasmota_relay_table_width . "px";
$attrib['style']['width'] = $tasmota_relay_table_width . "px";
define("TASMOTA_RELAY_TABLE_SELECT_TH_ATTRIBUTES", $attrib);

$attrib = array('rowspan' => $js_output_rowspan);
$attrib['class'] = 'tasmota_buttons_area';
$attrib['valign'] = "top";
$attrib['colspan'] = 2;
$attrib['style']['min-width'] = $button_area_width . "px";
$attrib['style']['max-width'] = $button_area_width . "px";
$attrib['style']['width'] = $button_area_width . "px";
$attrib['style']['min-height'] = $button_area_height . "px";
$attrib['style']['max-height'] = $button_area_height . "px";
$attrib['style']['height'] = $button_area_height . "px";
define("COMMAND_BUTTON_ATTRIBUTES", $attrib);

$attrib = array('class' => 'tasmota_buttons_area');
$attrib['style']['min-width'] = $button_area_width . "px";
$attrib['style']['max-width'] = $button_area_width . "px";
$attrib['style']['width'] = $button_area_width . "px";
$attrib['style']['min-height'] = $button_area_height . "px";
$attrib['style']['max-height'] = $button_area_height . "px";
$attrib['style']['height'] = $button_area_height . "px";
define("COMMAND_BUTTON_DIV_ATTRIBUTES", $attrib);

$attrib = array('class' => "tasmota_horizontal_result");
$attrib['colspan'] = $horizontal_result_colspan;
$attrib['valign'] = "middle";
$attrib['style']['min-width'] = $horizontal_result_width . "px";
$attrib['style']['max-width'] = $horizontal_result_width . "px";
$attrib['style']['width'] = $horizontal_result_width . "px";
$attrib['style']['min-height'] = $horizontal_result_height . "px";
$attrib['style']['max-height'] = $horizontal_result_height . "px";
$attrib['style']['height'] = $horizontal_result_height . "px";
define("HORIZONTAL_RESULT_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_horizontal_caption";
$attrib['colspan'] = $horizontal_result_colspan;
$attrib['style']['min-width'] = $horizontal_result_width . "px";
$attrib['style']['max-width'] = $horizontal_result_width . "px";
$attrib['style']['width'] = $horizontal_result_width . "px";
define("HORIZONTAL_RESULT_CAPTION_TH", $attrib);


$attrib = array();
$attrib['class'] = 'tasmota_tiny_result';
$attrib['style']['min-width'] = $tiny_result_width . "px";
$attrib['style']['max-width'] = $tiny_result_width . "px";
$attrib['style']['width'] = $tiny_result_width . "px";
$attrib['style']['min-height'] = $tiny_result_height . "px";
$attrib['style']['max-height'] = $tiny_result_height . "px";
$attrib['style']['height'] = $tiny_result_height . "px";
define("TINY_RESULT_DIV_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = TABLE_COLSPAN;
$attrib['class'] = "tasmota_main_table";

$attrib['style']['min-width'] = $main_table_width . "px";
$attrib['style']['max-width'] = $main_table_width . "px";
$attrib['style']['width'] = $main_table_width . "px";

define("MAIN_TABLE_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = TABLE_COLSPAN;
$attrib['style']['min-width'] = $main_table_width . "px";
$attrib['style']['max-width'] = $main_table_width . "px";
$attrib['style']['width'] = $main_table_width . "px";
$attrib['class'] = "tasmota_main_table_title";
define("MAIN_TABLE_TITLE_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = 'tasmota_jscript_feed';
$attrib['colspan'] = $tasmota_feed_colspan;
$attrib['id'] = JAVASCRIPT_DUMP_ID;
$attrib['style']['min-width'] = $horizontal_result_width . "px";
$attrib['style']['max-width'] = $horizontal_result_width . "px";
$attrib['style']['width'] = $horizontal_result_width . "px";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
define("TASMOTA_JSCRIPT_FEED_ATTIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area";
$attrib['colspan'] = $bottom_button_area_colspan;
$attrib['style']['min-width'] = $bottom_button_area_width . "px";
$attrib['style']['max-width'] = $bottom_button_area_width . "px";
$attrib['style']['width'] = $bottom_button_area_width . "px";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
define("BOTTOM_BUTTON_AREA_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area";
$attrib['style']['min-width'] = $bottom_button_area_width . "px";
$attrib['style']['max-width'] = $bottom_button_area_width . "px";
$attrib['style']['width'] = $bottom_button_area_width . "px";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
define("BOTTOM_BUTTON_AREA_DIV_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_relay";
$attrib['colspan'] = $a = $bottom_vertical_button_no_caption_colspan;
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
$attrib['style']['min-width'] = $base_column_width * $a . "px";
$attrib['style']['max-width'] = $base_column_width * $a . "px";
$attrib['style']['width'] = $base_column_width * $a . "px";
define("BOTTOM_RELAY_TD_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_relay";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
$attrib['style']['min-width'] = $base_column_width * $a . "px";
$attrib['style']['max-width'] = $base_column_width * $a . "px";
$attrib['style']['width'] = $base_column_width * $a . "px";
define("BOTTOM_RELAY_DIV_CONTAINER_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = $bottom_vertical_button_colspan;
$attrib['class'] = "tasmota_select_buttons_area_vertical";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";
$attrib['style']['min-width'] = $bottom_vertical_button_width . "px";
$attrib['style']['max-width'] = $bottom_vertical_button_width . "px";
$attrib['style']['width'] = $bottom_vertical_button_width . "px";
define("BOTTOM_VERTICAL_BUTTONS_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area_vertical";
$attrib['style']['min-height'] = $bottom_row_height . "px";
$attrib['style']['max-height'] = $bottom_row_height . "px";
$attrib['style']['height'] = $bottom_row_height . "px";

$attrib['style']['min-width'] = $bottom_vertical_button_width . "px";
$attrib['style']['max-width'] = $bottom_vertical_button_width . "px";
$attrib['style']['width'] = $bottom_vertical_button_width . "px";
define("BOTTOM_VERTICAL_BUTTONS_DIV_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['colspan'] = $bottom_hr_td_colspan;
$attrib['class'] = "tasmota_hr_td";
$attrib['style']['min-width'] = $bottom_hr_td_width . "px";
$attrib['style']['max-width'] = $bottom_hr_td_width . "px";
$attrib['style']['width'] = $bottom_hr_td_width . "px";
define("BOTTOM_HR_TD", $attrib);


$attrib = array();
$attrib['colspan'] = $small_hr_td_colspan;
$attrib['class'] = "tasmota_hr_td";
$attrib['style']['min-width'] = $small_hr_td_width . "px";
$attrib['style']['max-width'] = $small_hr_td_width . "px";
$attrib['style']['width'] = $small_hr_td_width . "px";

define("BOTTOM_SMALL_HR_TD", $attrib);


$attrib = array();
$attrib['colspan'] = $bottom_title_td_colspan;
$attrib['class'] = "tasmota_bottom_title_td";
$attrib['style']['min-width'] = $bottom_title_td_width . "px";
$attrib['style']['max-width'] = $bottom_title_td_width . "px";
$attrib['style']['width'] = $bottom_title_td_width . "px";

define("BOTTOM_TITLE_TD", $attrib);
$attrib = array();
define("BOTTOM_HR", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_checkbox_td";
$attrib['style']['min-width'] = $bottom_select_td_width . "px";
$attrib['style']['max-width'] = $bottom_select_td_width . "px";
$attrib['style']['width'] = $bottom_select_td_width . "px";
$attrib['colspan'] = $bottom_select_colspan;
define("BOTTOM_SELECT", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_bottom_manual_command_td";
$attrib['style']['min-width'] = $bottom_manual_command_width . "px";
$attrib['style']['max-width'] = $bottom_manual_command_width . "px";
$attrib['style']['width'] = $bottom_manual_command_width . "px";
$attrib['colspan'] = $bottom_manual_command_colspan;
define("BOTTOM_MANUAL_COMMAND_TD_ATTRIBUTES", $attrib);


define("BOTTOM_BUTTON_BREAKPOINT", 50);






?>