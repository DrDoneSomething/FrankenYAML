<?php
define("CALLED_FROM_YAML_HELPER", defined("IN_YAML_HELPER"));
$in_yaml_helper = defined("IN_YAML_HELPER");
$start_dir = "./";
if (($base_filename = basename(__file__)) == basename($_SERVER["SCRIPT_FILENAME"])) {
    define("IN_YAML_HELPER", true);
    $start_dir = "../";
    require_once ("../frankenyaml_includes.php");
    define("TASMOTA_FUNCTIONS_DIRECTORY", "tasmota_functions");
} else
    define("TASMOTA_FUNCTIONS_DIRECTORY", EXTENSIONS_DIRECTORY .
        "/tasmota_functions");

define("START_DIR", $start_dir);

$home_url = "./?extension=" . pretty_filename(($base_filename));

define("HOME_URL", $home_url);

foreach (scandir(TASMOTA_FUNCTIONS_DIRECTORY) as $filename) {
    $path = TASMOTA_FUNCTIONS_DIRECTORY . '/' . $filename;
    if (is_file($path) && substr($filename, -4) == ".php") {
        require $path;
    }
}
switch (hijack_page()) {
    case "hard_redirect":
        hard_redirect();
    case "return":
        return;

    default:
        break;
}


js_pass_exec_vars();

$attrib = MAIN_TABLE_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<table $attrib>";
$attrib = MAIN_TABLE_TITLE_ATTRIBUTES;
$attrib = create_element_attributes($attrib);

echo '<tr><th ' . $attrib . '>TASMOTAS</th></tr>';

echo '<tr>';


$attrib = ITEM_CHECKBOX_TH_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
$img = '<img src="'.stored_file("check.png").'" />';
echo "<th $attrib>$img</th>";



$attrib = HORIZONTAL_RESULT_CAPTION_TH;
$attrib = create_element_attributes($attrib);
foreach (DISPLAY_VALUES as $alt_caption => $key) {
    if(is_numeric($alt_caption))
        $pretty_key = ucwords(str_replace("_", " ", $key));
    else
        $pretty_key = $alt_caption;
    echo "<th $attrib>" . $pretty_key . "</th>";

}
echo '</tr>';
$vertical_count = count(DISPLAY_VALUES_VERTICAL);
$rowspan = " rowspan=\"$vertical_count\" ";
$item_count = 1;

$select_side_column = array();
$tasmota_list = get_tasmota_list();
foreach ($tasmota_list as $index => $info) {
    $item_data = array();
    foreach (array_merge(DISPLAY_VALUES, DISPLAY_VALUES_VERTICAL,
        DISPLAY_VERTICAL_RELAYS) as $info_key => $key) {
        $info_key = is_numeric($info_key) ? $key : $info_key;
        if (isset($info[$info_key]))
            $value = $info[$info_key];
        else
            $value = "";
        $item_data[$key] = $value;
    }
    $hostname = $item_data['hostname'];
    $ip_address = $item_data['ip_address'];
    //$$key
    $tr_classname = tr_classname($hostname);
    $tr_classname_attribute = " class=\"$tr_classname\" ";
    if (!$hostname) {
        echo '<tr ' . $tr_classname_attribute . '><td colspan="' . TABLE_COLSPAN . '">';
        echo 'Corrupt entry removed: ' . smart_nl2br($info) . '</td></tr>';
        remove_hostname_from_db($index);
        continue;
    }

    $commands_enabled = true;
    if ($hostname != $index && !SCAN_MODE) {
        $commands_enabled = false;
        echo '<tr ' . $tr_classname_attribute . '><td colspan="' . TABLE_COLSPAN . '">';
        echo 'Hostname mismatch (' . "H: $hostname, I:$index)" .
            ' disabling commands. Rescan to fix. Disabling command buttons.</td></tr>';
    }


    $return_id = $hostname;

    // first col
    $select = make_select_input($hostname);
    $select .= json_div($hostname, "tr_classname", $tr_classname);
    
    /*
    if (LIST_DISPLAY_MODE == "short") {
        $attrib = TINY_RESULT_DIV_ATTRIBUTES;
        $attrib['id'] = "{$hostname}_td_js_output";
        $attrib = create_element_attributes($attrib);
        
        $select .= "<div $attrib>&nbsp;</div>";
    }*/


    $last_refresh = time_since($item_data['last_refresh']);


    // last col
    
    $remove_button = SCAN_MODE ? "" : " | ".remove_tasmota_button($hostname);
    $item_data['other'] = make_tasmota_cmnd_input($hostname).$remove_button;
    $horizontal_values = "";
    foreach (DISPLAY_VALUES as $key) {
        $data = $item_data[$key];
        $attrib = HORIZONTAL_RESULT_ATTRIBUTES;
        $attrib['id'] = $hostname . "_td_$key";
        $attrib = create_element_attributes($attrib);
        $horizontal_values .= "<td $attrib>" . ($data ? format_display_value($data, $key) :
            "&nbsp;") . "</td>";
    }

    echo "<tr $tr_classname_attribute>";
    dump_td($select,ITEM_CHECKBOX_TD_ATTRIBUTES);
    echo $horizontal_values;
    echo '</tr>';
    //--------------------------------- below is for full display ------------------------
    if (LIST_DISPLAY_MODE == "short")
        continue;

    $attrib = create_element_attributes(array_merge(JS_OUTPUT_ATTRIBUTES, array('id' =>
            $hostname . "_td_js_output")));

    $main_result_td = "<td $attrib>&nbsp;</td>";


    $vertical_trs = array();
    foreach (DISPLAY_VALUES_VERTICAL as $key) {
        $this_tr = "";
        $attrib = DISPLAY_VALUES_VERTICAL_CAPTION_ATTRIBUTES;
        $attrib = create_element_attributes($attrib);
        // SKIPS FIRST TR!
        if ($vertical_trs)
            $this_tr = "<tr $tr_classname_attribute>";
        $this_tr .= "<td $attrib>";
        $this_tr .= format_vertical_display_name($key, $hostname);


        $attrib = DISPLAY_VALUES_VERTICAL_ATTRIBUTES_VALUE;
        $attrib['id'] = $hostname . "_td_$key";
        $attrib = create_element_attributes($attrib);

        $this_tr .= "</td><td $attrib>";
        if ($item_data[$key])
            $this_tr .= format_display_value($item_data[$key], $key, $hostname, $key);
        else
            $this_tr .= "[No Saved Value]";

        $this_tr .= "</td></tr>";
        $vertical_trs[] = $this_tr;
    }


    // Pretty much just the relay
    // I think it might break otherwise
    $attrib = DISPLAY_VERTICAL_RELAYS_ATTRIBUTES;
    $attrib['id'] = $hostname . "_td_RelayName";
    $attrib = create_element_attributes($attrib);
    $relay_td = "<td $attrib>";
    $relay_td .= format_display_value($item_data['RelayName'], "RelayName", $hostname,
        "RelayName");
    $relay_td .= "</td>";


    $command_buttons = array();

    if ($commands_enabled) {
        $i = 1;
        foreach (COMMAND_BUTTONS as $info_key => $caption) {
            if (strpos($info_key, "cm:") === 0) {
                list($wut, $cmnd) = explode(":", $info_key, 2);
                $value = command_button($cmnd, $caption, $hostname, $hostname);
            } else
                $value = "[$info_key:$caption]";
            $command_buttons[] = $value;
            $i++;
        }

        $psb = popup_select_buttons();
        $command_buttons = array_merge($command_buttons, $psb);
    } else {
        $command_buttons[] = "[Commands Disabled]";
    }


    echo "<tr $tr_classname_attribute>";

    $attrib = COMMAND_BUTTON_ATTRIBUTES;
    $attrib = create_element_attributes($attrib);
    echo "<td $attrib>";

    $attrib = COMMAND_BUTTON_DIV_ATTRIBUTES;
    $attrib = create_element_attributes($attrib);
    echo "<div $attrib>";
    echo implode("", $command_buttons);
    echo "</div></td>";

    echo $main_result_td;
    echo $relay_td;
    echo implode("", $vertical_trs);
    echo '</tr>';

    $item_count++;
}
$hostname = $return_id = false;

$psb = popup_select_buttons("selected");
$all_buttons = $psb;

foreach (COMMAND_BUTTONS as $info_key => $key) {
    $info_key = is_numeric($info_key) ? $key : $info_key;
    if (strpos($info_key, "cm:") === 0) {
        list($wut, $cmnd) = explode(":", $info_key, 2);
        $value = command_button_selected($cmnd, $key);
        if (SCAN_MODE && substr($key, -2) == "**") {
            $onload_commands[] = js_exec_selected($cmnd);
        } elseif (!SCAN_MODE && substr($key, -1) == "*") {
            $onload_commands[] = js_exec_selected($cmnd);
        }
    } else
        $value = "[$caption:$info_key]";
    $all_buttons[] = $value;
}


$side_buttons = array();
foreach (DISPLAY_VALUES_VERTICAL as $cmnd) {
    if (!tasmota_has_reference($cmnd, 'bool'))
        continue;
    $value = command_button_selected($cmnd, $cmnd);
    $side_buttons[] = $value;
}

$onload_commands = array();
$onload_commands[] = js_exec_selected(DEFAULT_CMND);

$select = '<input type="checkbox" onchange="set_all_checkboxes_to_me(this)" ';
$select .= 'id="select_all_hostnames_checkbox" class="hostname" value="1" name="select_all_hostnames" />
    <script>newfun = function() { var checkitall= document.getElementById(\'select_all_hostnames_checkbox\'); checkitall.click();
    ';
$select .= implode("\n", $onload_commands);
$select .= '};
    addFunctionToOnload(newfun);' . "\n";

$select .= '</script>';

echo '<tr>';

$attrib = BOTTOM_SELECT;
$attrib = create_element_attributes($attrib);
echo "<td $attrib>" . $select . "</td>";


$attrib = BOTTOM_SMALL_HR_TD;
$attrib = create_element_attributes($attrib);
echo "<td $attrib><b>Select All</b></td>";
echo "<td $attrib><hr /></td>";


$attrib = BOTTOM_MANUAL_COMMAND_TD_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<td $attrib>";
$cmnd_input =  make_tasmota_cmnd_input(JAVASCRIPT_DUMP_ID_PREFIX);
echo format_display_value($cmnd_input,"tasmota_command_input");
echo "</td>";


$attrib = BOTTOM_SMALL_HR_TD;
$attrib = create_element_attributes($attrib);
echo "<td $attrib><hr /></td>";

$attrib = BOTTOM_TITLE_TD;
$attrib = create_element_attributes($attrib);
echo "<td $attrib>Apply To All</td>";

$attrib = BOTTOM_HR_TD;
$hr_td_attrib = create_element_attributes($attrib);
$attrib = BOTTOM_HR;
$attrib = create_element_attributes($attrib);
echo "<td $hr_td_attrib><hr $attrib /></td>";


echo '</tr><tr>';

$attrib = TASMOTA_JSCRIPT_FEED_ATTIBUTES;
$attrib = create_element_attributes($attrib);

echo "<td $attrib >&nbsp;</td>";

$attrib = BOTTOM_BUTTON_AREA_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<td  $attrib>";

$attrib = BOTTOM_BUTTON_AREA_DIV_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<div $attrib>";
$i = 1;
foreach ($all_buttons as $button) {
    echo $button . ($i && !($i % BOTTOM_BUTTON_BREAKPOINT) ? "<br />" : " ");
    $i++;
}

echo '</div>';

echo '</td>';


$attrib = BOTTOM_VERTICAL_BUTTONS_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<td  $attrib>";
$attrib = BOTTOM_VERTICAL_BUTTONS_DIV_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<div $attrib>";
echo implode("<br />", $side_buttons);
echo '</div></td>';


$attrib = BOTTOM_RELAY_TD_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<td  $attrib>";
$attrib = BOTTOM_RELAY_DIV_CONTAINER_ATTRIBUTES;
$attrib = create_element_attributes($attrib);
echo "<div $attrib>";
if (LIST_DISPLAY_MODE == "full")
    echo format_display_value('selected', 'RelayName', 'selected');
echo '</div>';
echo '</td>';


echo '</tr>';

echo '</table><br /><hr /><br />';
//$rads = array("192.168.3." => "192.168.3.", "192.168.1." => "192.168.1.");
//iradio_array("ip_prefix", $rads, "192.168.3.");
form_close();
echo '<table><tr><td valign="top">';
display_add_tasmota_box();

echo '</td><td width="30">&nbsp;</td><td valign="top">';

display_tasmota_login();

echo "</td></tr></table>";

?>