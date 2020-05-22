<?php

defined("IN_YAML_HELPER") || die("not in helper");


function soft_error($text, $title = "")
{
    $js_log_title = $title ? "$title: " : "";
    js_log($js_log_title . $text);
    popup_msg_on_load($text, $title);

}
function command_button_selected($array, $caption, $title = false, $close_popup = false,
    $relay = false)
{

    $js_command = js_exec_selected($array, $relay);

    if ($close_popup)
        $js_command .= " collapse_warnings();";

    $button_id = "selected_send_$caption" . "_button";

    if (!$title) {
        $ref = tasmota_has_reference($array, "tooltip");
        if ($ref)
            $title = $ref;
        else
            $title = smart_nl2br($array);

    }


    $tooltip = ' title="' . htmlentities(str_replace("\n", " ", $title)) . '" ';
    return make_button($button_id, $js_command, $caption, $title, false,
        "wrap_button");
    return '<span><button  class="button_list" id="' . $button_id .
        '" type="button" onclick="' . $js_command . '">' . $caption .
        '</button><span class="tooltiptext">' . $title . '</span></span>';
}

function click_all($caption)
{
    //
    $onclick = click_all_js($caption);


    $button_id_suffix = "_$caption" . "_button";
    return '<button class="button_list" type="button" onclick="' . $onclick . '">' .
        $caption . '</button>';
}
//CALLED WITHIN LOOP TO GET HOSTNAME AND RETURN ID


function command_button($array, $caption, $data_id, $return_id, $title = false,
    $close_popup = false)
{

    $js_command = js_exec_command($array, $data_id, $return_id);
    if ($close_popup)
        $js_command .= " collapse_warnings();";

    $button_id = $data_id . "_$caption" . "_button";
    if (!$title) {
        $ref = tasmota_has_reference($array, "tooltip");
        if ($ref)
            $title = $ref;
        else
            $title = smart_nl2br($array);

    }

    $tooltip = ' title="' . htmlentities(str_replace("\n", " ", $title)) . '" ';

    $button = make_button($button_id, $js_command, $caption, $title, false,
        "wrap_button");
    return $button;
    return '<span><button  class="button_list" id="' . $button_id .
        '" type="button" onclick="' . $js_command . '">' . $caption .
        '</button><span class="tooltiptext">' . $title . '</span></span>';

}
function popup_select_buttons($variable = array(), $breakpoint = false, $output_type =
    "array")
{
    if ($variable === "selected")
        $variable = array("hostname" => "Selected Tasmotas", "ip_address" => "127.0.0.1");
    $buttons = array();
    $buttons[] = ["*SetOptions Menu*", "setoption", $variable, "Show all Setoptions"];
    $buttons[] = ["*Switchmode Menu*", "switchmode", $variable,
        "Show all Switchmodes"];
    $buttons[] = ["*PowerOnState Menu*", "poweronstate", $variable,
        "Show all PowerOnStates"];
    $buttons[] = ["*STATUS Menu*", "status", $variable, "Show all STATUS updates"];
    $html = array();
    $button_num = 0;
    foreach ($buttons as $button) {
        $button_num++;
        if ($breakpoint && $button_num % $breakpoint == 0)
            $html[] = "<br />";
        $html[] = popup_select_button($button[0], $button[1], $button[2], $button[3]);


    }
    if ($output_type == "array")
        return $html;

    return implode(" ", $html);


}
//CALLED WITHIN LOOP TO GET HOSTNAME AND RETURN ID and IP ADDRESS
function popup_select_button($caption, $mode, $variable = array(), $tooltip = "")
{
    // ideally, these vars are passed in $variable
    // it'll  try and grab them if they are not
    //global $hostname,   $ip_address;

    $required_variables = array("hostname", "ip_address");

    while (!is_array($variable)) {
        if (!$variable) {
            $variable = array();
            break;
        }
        if (!strpos($variable, "="))
            die("Variable has no = sign : $variable in popup_select_button");
        list($key, $value) = explode("=", $variable);
        $variable = array($key => $value);
        break;
    }
    $required_missing = false;
    foreach ($required_variables as $rv) {
        $required_missing = $rv;
        if (!isset($variable[$rv])) {
            global $$rv;
            if (!isset($$rv) || !$$rv)
                break;
            $variable[$rv] = $$rv;

        }
        $required_missing = false;
    }
    if ($required_missing)
        die("popup_select_button fatal error, missing $required_missing");

    $return_id = $variable['hostname'];
    $return_id_get = js_format_return_id($return_id);

    $var_append_array = array(
        "return_id" => $return_id_get,
        "list_name" => list_name(),
        "popup_select" => $mode);


    $var_append_array = array_merge($var_append_array, $variable);
    foreach ($var_append_array as $key => $value)
        $var_append_array[$key] = "$key=" . urlencode($value);

    $var_append = implode("&", $var_append_array);

    if (!$tooltip)
        $tooltip = "Popup with $mode commands for $caption";

    $button_id = $return_id . "_$caption" . "_button";
    $js_url = JS_PATH . "?$var_append";
    $js_command = "dhtmlLoadScriptAddToQueue('$js_url');";
    return make_button($button_id, $js_command, $caption, $tooltip);

    $tooltip_attrib = ' title="' . $tooltip . '" ';
    return '<span><button  class="button_list" id="' . $button_id .
        '" type="button" onclick="dhtmlLoadScriptAddToQueue(\'' . $js_url . '\');">' . $caption .
        '</button><span class="tooltiptext">' . $tooltip . '</span></span>';

}
function make_button($button_id_or_input, $js_command = false, $caption = false,
    $tooltip = false, $extra_attributes = false, $div_wrap = "wrap_button")
{
    $required_vars = array(
        "onclick" => "js_command",
        "id",
        "caption");
    $other_vars = array(
        "tooltip",
        "extra_attributes",
        'div_wrap');


    if (is_array($button_id_or_input)) {
        if (!$extra_attributes)
            $extra_attributes = array();
        elseif (!is_array($extra_attributes))
            $extra_attributes = array($extra_attributes);

        foreach (array_merge($required_vars, $other_vars) as $alt_key => $var) {
            if (is_numeric($alt_key))
                $alt_key = "na na na na na na na na, gettin jiggly witit";
            $unset = false;
            if (isset($button_id_or_input[$var]) && ($unset = $var))
                $$var = $button_id_or_input[$var];
            elseif (isset($button_id_or_input[$alt_key]) && ($unset = $alt_key))
                $$var = $button_id_or_input[$alt_key];
            if ($unset)
                unset($button_id_or_input[$unset]);
        }

        $extra_attributes = array_merge($button_id_or_input, $extra_attributes);
    } else
        $id = $button_id_or_input;

    $vars_so_far = array();
    $fail = false;
    foreach ($required_vars as $var) {
        if (!isset($$var)) {
            $error = "not set";
            $vars_so_far[$var] = "FAIL make_button required var $var: $error";
            $fail = true;
            continue;
        }
        if ($$var === false) {
            $error = "invalid: " . smart_nl2br($$var);
            $vars_so_far[$var] = "FAIL make_button required var $var: $error";
            $fail = true;
        }
        $vars_so_far[$var] = $$var;

    }
    if ($fail) {
        $error = $error . " \n\n Inputted: " . smart_nl2br($vars_so_far);
        js_log($error);
        js_die($error);
    }


    $attrib = array(
        "id" => $id,
        "type" => "button",
        "onclick" => $js_command,
        "class" => "button_list");
    $attributes = create_element_attributes($extra_attributes, $attrib);

    $tooltip_attributes = array("class" => "tooltiptext", "id" => "{$id}_tooltip");
    $tt_array = array();
    if (is_array($tooltip) && $tooltip) {
        $tt_array = $tooltip;
        $acceptable_tt_innerHTML = array(
            "contents",
            "text",
            "innerHTML");
        foreach ($acceptable_tt_innerHTML as $key)
            if (isset($tooltip[$key])) {
                $tooltip = $tooltip[$key];
                unset($tt_array[$key]);
                break;
            }

        if (is_array($tooltip))
            die("make_button FAILED: tooltip was array but had no text");
    }
    $tt_attributes = create_element_attributes($tt_array, $tooltip_attributes);

    if ($tooltip)
        $button = '<span><button ' . $attributes . '>' . $caption . '</button><span ' .
            $tt_attributes . '>' . $tooltip . '</span></span>';
    else
        $button = '<button ' . $attributes . '>' . $caption . '</button>';
    if ($div_wrap) {
        if (is_array($div_wrap))
            $attrib = create_element_attributes($div_wrap);
        elseif (is_string($div_wrap))
            $attrib = create_element_attributes(array("class" => $div_wrap));
        else
            $attrib = "";
        return "<div $attrib>" . $button . '</div>';
    } else
        return $button;

}

//command_button($array, $caption, $data_id, $return_id, $title = false, $close_popup = false)
function make_tasmota_cmnd_input($data_id, $return_id = false, $close_popup = false)
{
    if (!$return_id)
        $return_id = $data_id;
    $return_id_slashed = addslashes($return_id);

    $id_start = "{$data_id}_tasmota_cmnd_";
    $textbox_js = "tasmota_manual_command('$return_id_slashed','textbox_update',this.value); return true;";
    $checkbox_js = "tasmota_manual_command('$return_id_slashed','get_reference',this.value); return true;";
    if ($data_id == JAVASCRIPT_DUMP_ID_PREFIX)
        $button_js = js_exec_selected("not used", false, "this.value");
    else
        $button_js = js_exec_command("not used", $data_id, $return_id, "this.value");

    $checkbox_id = "{$id_start}checkbox";
    $button_id = "{$id_start}button";
    $tooltip_id = "{$id_start}tooltip";
    $checkbox_tooltip_id = "{$id_start}checkbox_tooltip";
    $button_caption = "Exec";
    $button_tooltip_text = "Execute Command";

    if ($close_popup)
        $button_js .= " collapse_warnings();";

    $textbox_attributes = array(
        "type" => "text",
        "id" => $id_start . "text",
        "onfocus" => $textbox_js,
        "onclick" => $textbox_js,
        "onkeyup" => $textbox_js);
    $checkbox_tooltip_attributes = array("class"=>"tooltiptext","id"=>$checkbox_tooltip_id);
    
    $checkbox_attributes = array(
        "type" => "checkbox",
        "id" => $id_start . "checkbox",
        "onclick" => $checkbox_js);
    $button_tooltip = array("text" => "$button_tooltip_text", "id" => $tooltip_id);
    $button_attributes = array(
        "id" => $id_start . "button",
        "tooltip" => $button_tooltip,
        "disabled",
        "readonly",
        "caption" => $button_caption,
        "onclick" => $button_js);
    $checkbox_tooltip_text = "Click To Verify Command Before Executing";

    $textbox_attributes = create_element_attributes($textbox_attributes);
    $html = "<input $textbox_attributes />";
    $checkbox_attributes = create_element_attributes($checkbox_attributes);
    $checkbox_html = "<input $checkbox_attributes />";
    
    $checkbox_tooltip_attributes = create_element_attributes($checkbox_tooltip_attributes);
    $html .= '<span>' . $checkbox_html . "<span $checkbox_tooltip_attributes>" . $checkbox_tooltip_text .
        '</span>';
    $html .= make_button($button_attributes);
    $html = '<div class="tasmota_command_input">' . $html . '</div>';
    return $html;
}
function remove_tasmota_button($hostname, $return_id = false, $caption = "", $tooltip =
    "")
{

    if (SCAN_MODE)
        die("remove_tasmota_button cannot be run in IP scan mode");

    if (!$return_id)
        $return_id = $hostname;

    if (!$tooltip)
        $tooltip = "Remove $hostname from " . list_name();
    if (!$caption)
        $caption = "Remove from list";

    $return_id_get = js_format_return_id($return_id);

    $var_append_array = array(
        "return_id" => $return_id_get,
        "remove_hostname" => $hostname,
        "list_name" => list_name());


    foreach ($var_append_array as $key => $value)
        $var_append_array[$key] = "$key=" . urlencode($value);

    $var_append = implode("&", $var_append_array);
    $js_url = JS_PATH . "?$var_append";
    $button_id = $return_id . "_$caption" . "_button";
    $tooltip_attrib = ' title="' . $tooltip . '" ';

    return '<span><button  class="button_list" id="' . $button_id .
        '" type="button" onclick="dhtmlLoadScriptAddToQueue(\'' . $js_url . '\');">' . $caption .
        '</button><span class="tooltiptext">' . $tooltip . '</span></span>';


}
function json_div($hostname, $display_name, $value)
{
    // hostname is not always a hostname -- this is called within the loop,
    // it is really the ID prefix for HTML elements
    // in scan_mode it is garbage
    $json = @json_encode($value);
    return '<div style="display:none;position:absolute;" id="' . $hostname . '_' . $display_name .
        '_JSON">' . ($json) . '</div>';
}
function single_item_url($ip_address)
{
    return HOME_URL . '&list_name=' . TASMOTA_IP_SCAN_LIST . '&single_ip=' . $ip_address;
}
function format_display_value($value, $display_name, $hostname = false, $value_name = false)
{
    global $device_password, $device_username;
    if (!$hostname)
        global $hostname;
    // hostname is not always a hostname -- this is called within the loop, it is really the ID prefix for HTML elements
    // in scan_mode it is garbage


    $json_div = json_div($hostname, $display_name, $value);
    if (!$value_name)
        $value_name = $display_name;
    $value_name = strtolower($value_name);
    $class = "constrain_this";
    switch ($value_name) {
        case "other":
            return $value;
        default:
            $ref = tasmota_has_reference($value_name, "full", $value);
            if ($ref)
                $value = $ref;
            break;
        case "js_output":
            $json_div = "";
            //$class = "constrain_this_horizontally";
            return smart_nl2br($value);
            break;
        case 'ipaddress':
        case 'ip_address':
            $id = " id=\"{$hostname}_td_ip_address_string\" ";
            $html = '<a  ' . $id . ' target="_blank" href="http://' . "$device_username:$device_password@$value" .
                '/">' . $value . '</a>';
            $html .= '&nbsp;&nbsp;<span><a href="' . single_item_url($value) . '">';

            $html .= '<img src="' . EXTENSIONS_DIRECTORY .
                '/tasmota_functions/search_icon.png" /></a>';
            $html .= '<span class="tooltiptext">Isolate Item</span></span>';
            $value = $html;
            break;
        case 'hostname':
            $id = " id=\"{$hostname}_td_hostname_string\" ";
            $value = '<a  ' . $id . ' target="_blank" href="http://' . "$device_username:$device_password@$value" .
                '/">' . $value . '</a>';
            break;
        case 'relayname':
            $relay_select_mode = ($hostname == "selected" || $value == "selected");
            if ($relay_select_mode)
                $value = array(12345679 => "Selected Relays");
            if (is_array($value)) {
                $td_attrib_index_array = TASMOTA_RELAY_TABLE_TD_INDEX_ATTRIBUTES;
                if ($relay_select_mode) {
                    $table_attrib_array = TASMOTA_RELAY_TABLE_SELECT_ATTRIBUTES;
                    $th_attrib_array = TASMOTA_RELAY_TABLE_SELECT_TH_ATTRIBUTES;
                    $td_attrib_array = TASMOTA_RELAY_TABLE_SELECT_TD_ATTRIBUTES;
                    $td_result_attrib_array = $td_attrib_array;
                } else {
                    $table_attrib_array = TASMOTA_RELAY_TABLE_ATTRIBUTES;
                    $th_attrib_array = TASMOTA_RELAY_TABLE_TH_ATTRIBUTES;
                    $td_attrib_array = TASMOTA_RELAY_TABLE_TD_ATTRIBUTES;
                    $td_result_attrib_array = TASMOTA_RELAY_TABLE_TD_RESULT_ATTRIBUTES;
                }
                $html = "";
                $table_attrib = create_element_attributes($table_attrib_array);
                $html .= "<table $table_attrib>";

                $extra_commands = array_merge(RELAY_COMMANDS, JSON_FOR_RELAYS);
                ksort($extra_commands);
                foreach ($value as $relay_number => $name) {

                    if ($relay_select_mode)
                        $json_div = "";
                    else
                        $relay_number++;


                    $th_attrib = create_element_attributes($th_attrib_array);
                    $html .= "<tr><th $th_attrib>";

                    $relay_class = "tasmota_relay_checkbox";
                    $relay_checkbox_attrib_array = array("type" => 'checkbox', "class" => $relay_class);
                    if ($relay_select_mode) {
                        $relay_checkbox_attrib['value'] = 1;
                        $relay_checkbox_attrib_array['id'] = "select_all_relays_checkbox";
                        $relay_checkbox_attrib_array['name'] = "select_all_relays";
                        $relay_checkbox_attrib_array['onchange'] = "set_all_checkboxes_to_me(this)";

                    } else {
                        $relay_value = array(
                            "hostname" => $hostname,
                            "relay_number" => $relay_number,
                            "FriendlyName" => $name);
                        $relay_checkbox_attrib_array['value'] = htmlentities(JSON_encode($relay_value));

                        $relay_checkbox_attrib_array['id'] = "{$hostname}_checkbox_relay_$relay_number";
                        $relay_checkbox_attrib_array['name'] = "relays[]";
                        $relay_checkbox_attrib_array['onclick'] =
                            "add_relay_to_list(this.value,this.checked);";

                    }

                    $relay_checkbox_attrib = create_element_attributes($relay_checkbox_attrib_array);

                    $html .= "<input $relay_checkbox_attrib>";
                    $html .= $name;

                    $html .= "</th></tr>";
                    $command_buttons = array();
                    $elements = $all_elements = ["result_id", "index", "cmnd"];
                    $all_elements[] = "buttons";
                    foreach ($extra_commands as $cmnd => $disp_var) {
                        $no_result = $relay_select_mode;

                        if (strpos($cmnd, "[num]") !== false) {
                            list($index, $wutever) = explode("[num]", $cmnd);
                            $cmnd = str_replace("[num]", $relay_number, $cmnd);
                            $no_result = true;
                        } else {
                            $index = $cmnd;
                            $cmnd .= $relay_number;
                        }
                        if ($relay_select_mode) {
                            $caption = $disp_var;
                            $button = command_button_selected($cmnd, $caption, "$index (selected)", false, true);
                        } else {
                            $caption = "$disp_var$relay_number";
                            $button = command_button($cmnd, $caption, $hostname, $hostname);
                        }

                        if ($no_result)
                            $result_id = "";
                        else
                            $result_id = "{$hostname}_td_$disp_var$relay_number";

                        foreach ($elements as $e)
                            if ($$e || !isset($command_buttons[$index][$e]))
                                $command_buttons[$index][$e] = $$e;
                        $command_buttons[$index]['buttons'][$cmnd]['html'] = $button;
                        $command_buttons[$index]['buttons'][$cmnd]['caption'] = $caption;

                    }
                    $button_char_limit = 12;
                    foreach ($command_buttons as $index => $data) {
                        foreach ($all_elements as $e) {
                            $value = isset($data[$e]) ? $data[$e] : "";
                            $$e = $value;
                        }

                        $td_index_attrib = create_element_attributes($td_attrib_index_array);
                        $html .= "<tr><td $td_index_attrib><b>$index</b></td></tr>";
                        $td_attrib = create_element_attributes($td_attrib_array);
                        $html .= "<tr><td $td_attrib><div>";

                        $caption_chars_row = 0;
                        $button_count = 0;
                        foreach ($buttons as $button) {
                            $caption_chars_row += strlen($button['caption']);
                            if ($button_count && $caption_chars_row > $button_char_limit) {
                                $caption_chars_row = 0;
                            }
                            $html .= $button['html'];

                            $button_count++;
                        }
                        $html .= "</div></td></tr>";
                        $id = array("id" => $result_id);
                        $td_result_attrib = create_element_attributes($td_result_attrib_array, $id);
                        if ($result_id)
                            $html .= "<tr><td $td_result_attrib>[result]</td></tr>";
                        ;
                    }


                }
                $html .= "</table>";
                $value = $html;
            }
            break;

        case "mqtthost":
            $mqtt_hosts = MQTT_HOSTS;
            if (isset($mqtt_hosts[$value]))
                $value = $mqtt_hosts[$value];
        case "select":
            return $value;

    }
    $value = '<div class="'.$class.'">' . smart_nl2br($value) . '</div>' . $json_div;
    return $value;
}
function format_vertical_display_name($display_name, $return_id = false, $show_button = true)
{
    if ($show_button)
        $show_button = tasmota_has_reference($display_name, "bool");

    if (!$show_button)
        return ucwords(str_replace("_", " ", $display_name));
    if ($return_id)
        $button = command_button($display_name, $display_name, $return_id, $return_id);
    else
        $button = command_button_selected($display_name, $display_name);
    return $button;
}
function ahref_command($array, $caption)
{
    $url = construct_command($array);
    $url = __file__;
    return '<a href="' . $url . '">' . $caption . '</a>';
}
// RUN WITHIN THE LOOP!!
function construct_command_url($array)
{
    global $device_username, $device_password, $ip_address, $hostname;

    if (SCAN_MODE)
        $url = "http://$ip_address/cm?user=$device_username&password=$device_password&cmnd=";
    else
        $url = "http://$hostname/cm?user=$device_username&password=$device_password&cmnd=";

    $i = 0;
    if (is_string($array)) {
        $exploded = explode(";", $array);
        $array = array();
        foreach ($exploded as $cmnd) {
            $subsploded = explode(" ", trim($cmnd), 2);
            $cm = $subsploded[0];
            $cmval = isset($subsploded[1]) ? $subsploded[1] : false;
            if (isset($cm)) {
                echo "duplicate command $cm, skipped<br />";
                continue;

            }
            $array[$cm] = $cmval;
        }
    }
    $append_array = array();
    foreach ($array as $command => $value) {
        if (htmlentities($command) != $command) {
            echo "Error: Cannot construct command, command '$command' contains weird shit";
            return "";
        }
        if (htmlentities($value) != $value) {
            echo "Error: Cannot construct command, value '$value' contains weird shit";
            return "";
        }
        $command = trim($command);
        if (substr($command, 0, 7) == "Backlog") {
            if (!$i) {
                echo "Error: Cannot construct command, backlog too late command '$command' value '$value' ";
                return "";

            }
            $command = substr($command, -7);
            $command = trim($command);
        }
        $value = trim($value);
        if (!$i && count($array) > 1) {
            $command = "backlog $command";
        }
        if ($value !== false && $value !== "")
            $append_array[] = urlencode("$command $value");
        else
            $append_array[] = "$command";


        $i++;
    }
    return $url . implode(";", $append_array);

}

function cmnd_to_array($array)
{
    if (!is_array($array)) {

        $cmd_arr = explode(";", $array);
        $array = array();
        foreach ($cmd_arr as $this_commmand) {
            $this_commmand = trim($this_commmand);
            if (stripos($this_commmand, "backlog") === 0)
                $this_commmand = trim(substr($this_commmand, 7));
            if (strpos($this_commmand, " "))
                list($this_commmand, $tparam) = explode(" ", $this_commmand, 2);
            else
                $tparam = "";
            $array[$this_commmand] = $tparam;

        }
    }
    
    return $array;
    
}
// RUN WITHIN THE LOOP!!
function construct_command_string($array)
{
    $i = 0;
    if (!is_array($array)) {

        $array = cmnd_to_array($array);
    }
    $append_array = array();
    foreach ($array as $command => $value) {
        if (htmlentities($command) != $command) {
            js_die( "Error: Cannot construct command, command '$command' contains weird shit",false);
            return "";
        }
        if (htmlentities($value) != $value) {
            js_die( "Error: Cannot construct command, value '$value' contains weird shit",false);
            return "";
        }
        $command = trim($command);
        if (strtolower(substr($command, 0, 7)) == "backlog") {
            if (!$i) {
                js_die( "Error: Cannot construct command, backlog should be at beginning of command '$command' value '$value' ",false);
                return "";

            }
            $command = substr($command, -7);
            $command = trim($command);
        }
        $value = trim($value);
        if (!$i && count($array) > 1) {
            $command = "backlog $command";
        }
        if ($value !== false && $value !== "")
            $append_array[] = "$command $value";
        else
            $append_array[] = "$command";


        $i++;
    }
    return implode(";", $append_array);

}
function smart_nl2br($text,$limit_lines = 50000,$truncate_and_strip=false)
{
    if (!is_string($text) && !is_numeric($text)) {
        $output = array();

        if (is_array($text)) {
            everything_to_string($output, $text);
            $text = implode("\n", $output);
        } else {
            $ets = array($text);
            everything_to_string($output, $ets);
            $text = implode("\n", $output);
        }
    }

    $text = str_replace(array(
        "<br>\r\n",
        "<br />\r\n",
        "<br/>\r\n",
        "<br>",
        "<br />",
        "<br/>"), "\n", $text);
    $text = str_replace(array(
        "\r\n",
        "\n",
        "\r"), "<br />", $text);
    if($limit_lines!=50000 || $truncate_and_strip)
    {
        $lines = explode("<br />",$text);
        $text = "";
        $new_lines = array();
        for($i=0;$i<$limit_lines && isset($lines[$i]);$i++)
        {
            $line = $lines[$i];
            if($truncate_and_strip)
            {
                $line = strip_tags($line);
                $new_line = substr($line,0,$truncate_and_strip);
                if(strlen($new_line) != strlen($line))
                {
                    $new_line = substr($line,0,$truncate_and_strip-3);
                    $new_line.="...";
                }
                $line = $new_line;
            }
            $new_lines[$i] = $line;
        }
        $text = implode("<br />",$new_lines);
    }
    return $text;
}
function everything_to_string(&$output, $input_array, $indent = "")
{
    $indent .= "- ";
    if (!$input_array) {
        $output[] = $indent . "[empty]";
        return;
    }
    $to_string = false;
    if (!is_array($input_array)) {

        $input_array = array($input_array);
        $to_string = true;
    }
    foreach ($input_array as $key => $value) {
        $output_string = "$indent$key -> ";
        switch (gettype($value)) {
            case "boolean":
                $output[] = $output_string . ($value ? "true" : "false");
                break;
            case "string":
                $output[] = $output_string . "'$value'";
                break;
            case 'integer':
            case 'double':
                $output[] = $output_string . $value;
                break;
            case 'NULL':
                $output[] = $output_string . "NULL - ** POSSIBLE BUG! **";
                break;
            case 'array':
                //$output[] = "<pre>";
                $output[] = "$output_string: ";
                everything_to_string($output, $value, $indent);
                //$output[] = "</pre>";

                break;
            default:
                $output[] = $output_string . '<pre>' . nl2br(var_export($value, true)) .
                    '</pre>';
                break;
        }


    }

}
function tr_classname($hostname)
{
    return md5($hostname) . "_tr";
}
?>