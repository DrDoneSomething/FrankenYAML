<?php

defined("IN_YAML_HELPER") || die("not in helper");

$clean_buttons = array();
foreach($command_buttons as $cm => $cap)
{
    $result = array();
    if(isset($clean_buttons[$cap]))
    {
        $result = $clean_buttons[$cap];
        $ct = count($clean_buttons[$cap])+1;
        $command_buttons[$cm] = "$cap ($ct)";
    }
    $result[] = $cm;
    $clean_buttons[$cap] = $result;
}





define("COMMAND_BUTTONS", $command_buttons);
define('RELAY_COMMANDS', $relay_commands);
if ($list_display_mode == "full")
    $display_values = $display_values_FULL;
else
    $display_values = $display_values_SHORT;

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


define("JSON_TO_STORED", array_merge($shared_JSON, $stored_JSON));


$display_JSON = array_merge($shared_JSON, $display_JSON);
append_references_to_display($display_JSON);
//var_dump($display_JSON);
define("JSON_TO_DISPLAY", $display_JSON);


$base_column_width = 150;

$scrollbar_width = 22;
$checkbox_width = 24;
$horizontal_result_height = "60";
$horizontal_result_colspan = 2;

$item_checkbox_td_colspan = 1;
$item_checkbox_td_width = "40";
$item_checkbox_td_height = $horizontal_result_height;
$item_checkbox_total_width = $item_checkbox_td_width * $item_checkbox_td_colspan;


$horizontal_result_width = $base_column_width * $horizontal_result_colspan;

$vertical_results_total = count(DISPLAY_VALUES_VERTICAL);
$horizontal_results_total = count(DISPLAY_VALUES);
$horizontal_results_colspan_total = $horizontal_results_total * $horizontal_result_colspan;

$total_colspan = $horizontal_results_colspan_total + $item_checkbox_td_colspan;

define("TABLE_COLSPAN", $total_colspan);

$main_table_width = ($horizontal_result_width * count(DISPLAY_VALUES)) + $item_checkbox_total_width;

$js_output_colspan = TABLE_COLSPAN - ($horizontal_result_colspan * 3);
$js_output_rowspan = count(DISPLAY_VALUES_VERTICAL);

$js_output_width = $js_output_colspan * $base_column_width;
$vertical_result_width = floor(.66 * $horizontal_result_width);
$vertical_caption_width = floor(.33 * $horizontal_result_width);
// THIS IS THE TD THAT HOLDS ALL THE RELAYS
$vertical_relays_colspan = 2;
$vertical_relays_width = $vertical_relays_colspan *$base_column_width;


$vertical_result_height = $horizontal_result_height;
$vertical_caption_height = $vertical_result_height;
$vertical_relays_height = ($horizontal_result_height * (count(DISPLAY_VALUES_VERTICAL))) /
    count(DISPLAY_VERTICAL_RELAYS);


$js_output_height = $vertical_caption_height * (count(DISPLAY_VALUES_VERTICAL));



// ----------- button area ---------

$button_area_height = $js_output_height;
$button_area_colspan = $item_checkbox_td_colspan + 1;

$button_area_width = ($base_column_width * ($button_area_colspan - $item_checkbox_td_colspan)) +
    $item_checkbox_total_width;




//------------ RELAY RESULTS ---------------



$tasmota_relay_td_power_result_width = 36;
$tasmota_relay_td_power_height = 38;
$tasmota_relay_td_power_result_colspan = 1;
$tasmota_relay_td_power_colspan =1;

$tasmota_relay_td_result_height = 20;

$tasmota_relay_table_colspan = $tasmota_relay_td_power_colspan + $tasmota_relay_td_power_result_colspan;

$tasmota_relay_table_width = $vertical_relays_width - $scrollbar_width;
$tasmota_relay_table_height =false;


$tasmota_relay_td_power_result_height = $tasmota_relay_td_power_height;
$tasmota_relay_td_power_width = ($tasmota_relay_table_width - ($tasmota_relay_td_power_result_width *
    $tasmota_relay_td_power_result_colspan));

$tasmota_relay_th_height = false;
$tasmota_relay_th_width = $tasmota_relay_table_width;

$tasmota_relay_td_height = false;
$tasmota_relay_th_colspan = $tasmota_relay_table_colspan;



$tasmota_relay_td_colspan = $tasmota_relay_table_colspan;
$tasmota_relay_td_result_colspan = $tasmota_relay_td_colspan;
$tasmota_relay_td_width = $tasmota_relay_table_width; 

$tasmota_relay_td_result_width =$tasmota_relay_table_width;
$tasmota_relay_td_result_width = $tasmota_relay_table_width;




// ---------- bottom button stuff------
// changing this does nothing but ruin everything.

$small_hr_td_colspan = 1;

$bottom_row_height = $horizontal_result_height * 7;
$bottom_small_hr_tds_count = 2;
$bottom_title_td_colspan = 1;


$tiny_result_width = $horizontal_result_width - $checkbox_width;
$tiny_result_height = $horizontal_result_height;

$a = $bottom_manual_command_colspan = 2;
$a += $item_checkbox_td_colspan;
$a += $bottom_select_colspan = 1;
$a += $small_hr_td_colspan * $bottom_small_hr_tds_count;
$a += $bottom_title_td_colspan;
$bottom_hr_td_colspan = TABLE_COLSPAN - $a;

$a = $tasmota_feed_colspan = $item_checkbox_td_colspan + $horizontal_result_colspan;
$a += $bottom_vertical_button_colspan = 1;
$a += $bottom_vertical_button_no_caption_colspan = 2;


$tasmota_feed_width = $horizontal_result_width + $item_checkbox_total_width;

$bottom_button_area_colspan = TABLE_COLSPAN - $a;
$bottom_button_area_width = $bottom_button_area_colspan * $base_column_width;
$bottom_vertical_button_width = $bottom_vertical_button_colspan * $base_column_width;
$bottom_title_td_width = $bottom_title_td_colspan * $base_column_width;
$small_hr_td_width = $small_hr_td_colspan * $base_column_width;

$bottom_manual_command_width = $bottom_manual_command_colspan * $base_column_width;
$bottom_hr_td_width = $bottom_hr_td_colspan * $base_column_width;
$bottom_hr_width = $bottom_hr_td_width - 20;


$aliases = array();


$attrib = array();
$attrib['colspan'] = TABLE_COLSPAN;
$attrib['class'] = "tasmota_main_table";
$attrib['style']['min-width'] = $main_table_width;
$attrib['style']['max-width'] = $main_table_width;
$attrib['style']['width'] = $main_table_width;
define("MAIN_TABLE_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = TABLE_COLSPAN;
$attrib['style']['min-width'] = $main_table_width;
$attrib['style']['max-width'] = $main_table_width;
$attrib['style']['width'] = $main_table_width;
$attrib['class'] = "tasmota_main_table_title";
define("MAIN_TABLE_TITLE_ATTRIBUTES", $attrib);

$unique_name = "JS_OUTPUT_ATTRIBUTES";
$attrib = array(
    "valign=" => 'top',
    'rowspan' => $js_output_rowspan,
    'class' => "tasmota_main_result",
    'colspan' => $js_output_colspan);
$attrib['style']['min-width'] = $js_output_width;
$attrib['style']['max-width'] = $js_output_width;
$attrib['style']['width'] = $js_output_width;
$attrib['style']['min-height'] = $js_output_height;
$attrib['style']['max-height'] = $js_output_height;
$attrib['style']['height'] = $js_output_height;
define("$unique_name", $attrib);
$aliases['main_result']['result'] = $unique_name;


$attrib = array('class' => "tasmota_horizontal_result");
$attrib['colspan'] = $horizontal_result_colspan;
$attrib['valign'] = "middle";
$attrib['style']['min-width'] = $horizontal_result_width;
$attrib['style']['max-width'] = $horizontal_result_width;
$attrib['style']['width'] = $horizontal_result_width;
$attrib['style']['min-height'] = $horizontal_result_height;
$attrib['style']['max-height'] = $horizontal_result_height;
$attrib['style']['height'] = $horizontal_result_height;
define("HORIZONTAL_RESULT_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_horizontal_caption";
$attrib['colspan'] = $horizontal_result_colspan;
$attrib['style']['min-width'] = $horizontal_result_width;
$attrib['style']['max-width'] = $horizontal_result_width;
$attrib['style']['width'] = $horizontal_result_width;
define("HORIZONTAL_RESULT_CAPTION_TH", $attrib);


$unique_name = "ITEM_CHECKBOX_TH_ATTRIBUTES";
$attrib['colspan'] = $item_checkbox_td_colspan;
$attrib['style']['min-width'] = $item_checkbox_td_width;
$attrib['style']['max-width'] = $item_checkbox_td_width;
$attrib['style']['width'] = $item_checkbox_td_width;
$attrib['class'] = "tasmota_horizontal_caption";
define("$unique_name", $attrib);


$unique_name = "ITEM_CHECKBOX_TD_ATTRIBUTES";
$attrib['colspan'] = $item_checkbox_td_colspan;
$attrib['style']['min-width'] = $item_checkbox_td_width;
$attrib['style']['max-width'] = $item_checkbox_td_width;
$attrib['style']['width'] = $item_checkbox_td_width;
$attrib['style']['min-height'] = $item_checkbox_td_height;
$attrib['style']['max-height'] = $item_checkbox_td_height;
$attrib['style']['height'] = $item_checkbox_td_height;
$attrib['class'] = "tasmota_item_checkbox_td";
define("$unique_name", $attrib);


$unique_name = "DISPLAY_VALUES_VERTICAL_CAPTION_ATTRIBUTES";
$attrib = array("class" => "tasmota_vertical_caption");
$attrib['style']['min-width'] = $vertical_caption_width;
$attrib['style']['max-width'] = $vertical_caption_width;
$attrib['style']['width'] = $vertical_caption_width;
$attrib['style']['min-height'] = $vertical_caption_height;
$attrib['style']['max-height'] = $vertical_caption_height;
$attrib['style']['height'] = $vertical_caption_height;
define($unique_name, $attrib);
$aliases['display_values']['vertical']['caption'] = "$unique_name";

$unique_name = "DISPLAY_VALUES_VERTICAL_ATTRIBUTES_VALUE";
$attrib = array();
$attrib['class'] = 'tasmota_vertical_result';
$attrib['style']['min-width'] = $vertical_result_width;
$attrib['style']['max-width'] = $vertical_result_width;
$attrib['style']['width'] = $vertical_result_width;
$attrib['style']['max-height'] = $vertical_result_height;
$attrib['style']['height'] = $vertical_result_height;
define("$unique_name", $attrib);
$aliases['display_values']['vertical']['result'] = $unique_name;


$unique_name = "DISPLAY_VERTICAL_RELAYS_ATTRIBUTES";
$attrib = array();
$attrib["rowspan"] = $js_output_rowspan;
$attrib["class"] = "tasmota_relays_td";
$attrib['colspan'] = 2;
$attrib['style']['min-width'] = $vertical_relays_width;
$attrib['style']['max-width'] = $vertical_relays_width;
$attrib['style']['width'] = $vertical_relays_width;
$attrib['style']['min-height'] = $vertical_relays_height;
$attrib['style']['max-height'] = $vertical_relays_height;
$attrib['style']['height'] = $vertical_relays_height;
define("$unique_name", $attrib);
$aliases['display_values']['relays']['result'] = $unique_name;
$aliases['relays']['result'] = $unique_name;


$attrib = array('rowspan' => $js_output_rowspan);
$attrib['class'] = 'tasmota_buttons_area';
$attrib['valign'] = "top";
$attrib['colspan'] = $button_area_colspan;
$attrib['style']['min-width'] = $button_area_width;
$attrib['style']['max-width'] = $button_area_width;
$attrib['style']['width'] = $button_area_width;
$attrib['style']['min-height'] = $button_area_height;
$attrib['style']['max-height'] = $button_area_height;
$attrib['style']['height'] = $button_area_height;
define("COMMAND_BUTTON_ATTRIBUTES", $attrib);

$attrib = array('class' => 'tasmota_buttons_area');
$attrib['style']['min-width'] = $button_area_width;
$attrib['style']['max-width'] = $button_area_width;
$attrib['style']['width'] = $button_area_width;
$attrib['style']['min-height'] = $button_area_height;
$attrib['style']['max-height'] = $button_area_height;
$attrib['style']['height'] = $button_area_height;
define("COMMAND_BUTTON_DIV_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = 'tasmota_tiny_result';
$attrib['style']['min-width'] = $tiny_result_width;
$attrib['style']['max-width'] = $tiny_result_width;
$attrib['style']['width'] = $tiny_result_width;
$attrib['style']['min-height'] = $tiny_result_height;
$attrib['style']['max-height'] = $tiny_result_height;
$attrib['style']['height'] = $tiny_result_height;
define("TINY_RESULT_DIV_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = 'tasmota_jscript_feed';
$attrib['colspan'] = $tasmota_feed_colspan;
$attrib['id'] = JAVASCRIPT_DUMP_ID;
$attrib['style']['min-width'] = $tasmota_feed_width;
$attrib['style']['max-width'] = $tasmota_feed_width;
$attrib['style']['width'] = $tasmota_feed_width;
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
define("TASMOTA_JSCRIPT_FEED_ATTIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area";
$attrib['colspan'] = $bottom_button_area_colspan;
$attrib['style']['min-width'] = $bottom_button_area_width;
$attrib['style']['max-width'] = $bottom_button_area_width;
$attrib['style']['width'] = $bottom_button_area_width;
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
define("BOTTOM_BUTTON_AREA_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area";
$attrib['style']['min-width'] = $bottom_button_area_width;
$attrib['style']['max-width'] = $bottom_button_area_width;
$attrib['style']['width'] = $bottom_button_area_width;
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
define("BOTTOM_BUTTON_AREA_DIV_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_relay";
$attrib['colspan'] = $a = $bottom_vertical_button_no_caption_colspan;
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
$attrib['style']['min-width'] = $base_column_width * $a;
$attrib['style']['max-width'] = $base_column_width * $a;
$attrib['style']['width'] = $base_column_width * $a;
define("BOTTOM_RELAY_TD_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_relay";
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
$attrib['style']['min-width'] = $base_column_width * $a;
$attrib['style']['max-width'] = $base_column_width * $a;
$attrib['style']['width'] = $base_column_width * $a;
define("BOTTOM_RELAY_DIV_CONTAINER_ATTRIBUTES", $attrib);

$attrib = array();
$attrib['colspan'] = $bottom_vertical_button_colspan;
$attrib['class'] = "tasmota_select_buttons_area_vertical";
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;
$attrib['style']['min-width'] = $bottom_vertical_button_width;
$attrib['style']['max-width'] = $bottom_vertical_button_width;
$attrib['style']['width'] = $bottom_vertical_button_width;
define("BOTTOM_VERTICAL_BUTTONS_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['class'] = "tasmota_select_buttons_area_vertical";
$attrib['style']['min-height'] = $bottom_row_height;
$attrib['style']['max-height'] = $bottom_row_height;
$attrib['style']['height'] = $bottom_row_height;

$attrib['style']['min-width'] = $bottom_vertical_button_width;
$attrib['style']['max-width'] = $bottom_vertical_button_width;
$attrib['style']['width'] = $bottom_vertical_button_width;
define("BOTTOM_VERTICAL_BUTTONS_DIV_ATTRIBUTES", $attrib);


$attrib = array();
$attrib['colspan'] = $bottom_hr_td_colspan;
$attrib['class'] = "tasmota_hr_td";
$attrib['style']['min-width'] = $bottom_hr_td_width;
$attrib['style']['max-width'] = $bottom_hr_td_width;
$attrib['style']['width'] = $bottom_hr_td_width;
define("BOTTOM_HR_TD", $attrib);


$attrib = array();
$attrib['colspan'] = $small_hr_td_colspan;
$attrib['class'] = "tasmota_hr_td";
$attrib['style']['min-width'] = $small_hr_td_width;
$attrib['style']['max-width'] = $small_hr_td_width;
$attrib['style']['width'] = $small_hr_td_width;
define("BOTTOM_SMALL_HR_TD", $attrib);


$attrib = array();
$attrib['colspan'] = $bottom_title_td_colspan;
$attrib['class'] = "tasmota_bottom_title_td";
$attrib['style']['min-width'] = $bottom_title_td_width;
$attrib['style']['max-width'] = $bottom_title_td_width;
$attrib['style']['width'] = $bottom_title_td_width;

define("BOTTOM_TITLE_TD", $attrib);
$attrib = array();
define("BOTTOM_HR", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_select_checkbox_td";
$attrib['style']['min-width'] = $item_checkbox_td_width;
$attrib['style']['max-width'] = $item_checkbox_td_width;
$attrib['style']['width'] = $item_checkbox_td_width;
$attrib['colspan'] = $item_checkbox_td_colspan;
define("BOTTOM_SELECT", $attrib);

$attrib = array();
$attrib['class'] = "tasmota_bottom_manual_command_td";
$attrib['style']['min-width'] = $bottom_manual_command_width;
$attrib['style']['max-width'] = $bottom_manual_command_width;
$attrib['style']['width'] = $bottom_manual_command_width;
$attrib['colspan'] = $bottom_manual_command_colspan;
define("BOTTOM_MANUAL_COMMAND_TD_ATTRIBUTES", $attrib);


// ------------- RELAY RESULTS TABLE ------------





// STANDARD: relay table
$attrib = array();
$attrib['class'] = "tasmota_relay_table";
$attrib['style']['min-height'] = $tasmota_relay_table_height;
$attrib['style']['max-height'] = $tasmota_relay_table_height;
$attrib['style']['height'] = $tasmota_relay_table_height;
$attrib['style']['min-width'] = $tasmota_relay_table_width;
$attrib['style']['max-width'] = $tasmota_relay_table_width;
$attrib['style']['width'] = $tasmota_relay_table_width;
define("TASMOTA_RELAY_TABLE_ATTRIBUTES", $attrib);


// STANDARD: relay title and checkbox
$attrib = array();
$attrib['colspan'] = $tasmota_relay_th_colspan;
$attrib['style']['min-height'] = $tasmota_relay_th_height;
$attrib['style']['max-height'] = $tasmota_relay_th_height;
$attrib['style']['height'] = $tasmota_relay_th_height;
$attrib['colspan'] = $tasmota_relay_table_colspan ;
$attrib['style']['min-width'] = $tasmota_relay_th_width;
$attrib['style']['max-width'] = $tasmota_relay_th_width;
$attrib['style']['width'] = $tasmota_relay_th_width;
define("TASMOTA_RELAY_TABLE_TH_ATTRIBUTES", $attrib);

// STANDARD: td with buttons (not power)
$attrib = array();
$attrib['colspan'] = $tasmota_relay_td_colspan;
$attrib['style']['min-height'] = $tasmota_relay_td_height;
$attrib['style']['max-height'] = $tasmota_relay_td_height;
$attrib['style']['height'] = $tasmota_relay_td_height;
$attrib['style']['min-width'] = $tasmota_relay_td_width;
$attrib['style']['max-width'] = $tasmota_relay_td_width;
$attrib['style']['width'] = $tasmota_relay_td_width;
$attrib['class'] = "tasmota_relay_table_buttons_td";
define("TASMOTA_RELAY_TABLE_TD_ATTRIBUTES", $attrib);

// STANDARD: td with title of command button grouping
$attrib = array();
$attrib['colspan'] = $tasmota_relay_td_colspan ;
$attrib['style']['min-height'] = $tasmota_relay_td_height;
$attrib['style']['max-height'] = $tasmota_relay_td_height;
$attrib['style']['height'] = $tasmota_relay_td_height;
$attrib['style']['min-width'] = $tasmota_relay_td_width;
$attrib['style']['max-width'] = $tasmota_relay_td_width;
$attrib['style']['width'] = $tasmota_relay_td_width;
$attrib['class'] = "tasmota_relay_table_index_td";
define("TASMOTA_RELAY_TABLE_TD_INDEX_ATTRIBUTES", $attrib);

// STANDARD: result td attributes (no result in select)
$unique_name = "TASMOTA_RELAY_TABLE_TD_RESULT_ATTRIBUTES";
$attrib = array();
$attrib['style']['min-height'] = $tasmota_relay_td_result_height;
$attrib['style']['max-height'] = $tasmota_relay_td_result_height;
$attrib['style']['height'] = $tasmota_relay_td_result_height;
$attrib['colspan'] = $tasmota_relay_td_result_colspan;
$attrib['class'] = "tasmota_relay_result_td animated_result_container start";
$attrib['style']['min-width'] = $tasmota_relay_td_result_width;
$attrib['style']['max-width'] = $tasmota_relay_td_result_width;
$attrib['style']['width'] = $tasmota_relay_td_result_width;
define($unique_name, $attrib);

// STANDARD: result td attributes FOR POWER (no result in select)
$unique_name = "TASMOTA_RELAY_TABLE_TD_POWER_RESULT_ATTRIBUTES";
$attrib = array();
$attrib['class'] = "tasmota_relay_power_result_td animated_result_container start";
$attrib['colspan'] = $tasmota_relay_td_power_result_colspan;
$attrib['style']['min-height'] = $tasmota_relay_td_power_result_height;
$attrib['style']['max-height'] = $tasmota_relay_td_power_result_height;
$attrib['style']['height'] = $tasmota_relay_td_power_result_height;
$attrib['style']['min-width'] = $tasmota_relay_td_power_result_width;
$attrib['style']['max-width'] = $tasmota_relay_td_power_result_width;
$attrib['style']['width'] = $tasmota_relay_td_power_result_width;
define($unique_name, $attrib);


// STANDARD: result td attributes (no result in select)
$unique_name = "TASMOTA_RELAY_TABLE_TD_POWER_ATTRIBUTES";
$attrib = array();
$attrib['class'] = "tasmota_relay_table_buttons_td";
$attrib['colspan'] = $tasmota_relay_td_power_colspan ;
$attrib['style']['min-height'] = $tasmota_relay_td_power_height ;
$attrib['style']['max-height'] = $tasmota_relay_td_power_height  ;
$attrib['style']['height'] = $tasmota_relay_td_power_height ;
$attrib['style']['min-width'] = $tasmota_relay_td_power_width ;
$attrib['style']['max-width'] = $tasmota_relay_td_power_width ;
$attrib['style']['width'] = $tasmota_relay_td_power_width ;
define($unique_name, $attrib);


// SELECT (bottom): relay table
$attrib = array();
$attrib['colspan'] = $tasmota_relay_table_colspan ;
$attrib['class'] = "tasmota_relay_table";
$attrib['style']['min-width'] = $tasmota_relay_table_width;
$attrib['style']['max-width'] = $tasmota_relay_table_width;
$attrib['style']['width'] = $tasmota_relay_table_width;
define("TASMOTA_RELAY_TABLE_SELECT_ATTRIBUTES", $attrib);

// SELECT (bottom): td attributes of button grouping
$attrib = array();
$attrib['colspan'] = $tasmota_relay_td_colspan ;
$attrib['style']['min-width'] = $tasmota_relay_table_width;
$attrib['style']['max-width'] = $tasmota_relay_table_width;
$attrib['style']['width'] = $tasmota_relay_table_width;
$attrib['class'] = "tasmota_relay_table_buttons_td";
define("TASMOTA_RELAY_TABLE_SELECT_TD_ATTRIBUTES", $attrib);


// SELECT (bottom): NAME OF RELAY WITH CHECKBOX
$attrib = array();
$attrib['colspan'] = $tasmota_relay_table_colspan;
$attrib['style']['min-width'] = $tasmota_relay_table_width;
$attrib['style']['max-width'] = $tasmota_relay_table_width;
$attrib['style']['width'] = $tasmota_relay_table_width;
define("TASMOTA_RELAY_TABLE_SELECT_TH_ATTRIBUTES", $attrib);


// puts <br /> between buttons.. not really used aynmore
define("BOTTOM_BUTTON_BREAKPOINT", 50);


define("ELEMENT_SETTINGS", $aliases);

?>