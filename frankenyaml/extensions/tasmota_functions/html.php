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

    $id_caption=preg_replace( '/[\W]/', '', $caption);
    $button_id = "selected_send_$id_caption" . "_button";

    if (!$title) {
        $ref = tasmota_has_reference($array, "tooltip");
        if ($ref)
            $title = $ref;
        else
            $title = smart_nl2br($array);

    }


    $tooltip = ' title="' . htmlentities(str_replace("\n", " ", $title)) . '" ';
    return make_button($button_id, $js_command, $caption, $title, false);
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
    $id_caption=preg_replace( '/[\W]/', '', $caption);
    
    $button_id = $data_id . "_$id_caption" . "_button";
    $button_id = htmlentities($button_id);
    if (!$title) {
        $ref = tasmota_has_reference($array, "tooltip");
        if ($ref)
            $title = $ref;
        else
            $title = smart_nl2br($array);

    }


    $button = make_button($button_id, $js_command, $caption, $title, false);
    return $button;
    

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
    $tooltip = false, $extra_attributes = false, $div_wrap = false)
{
    $required_vars = array(
        "onclick" => "js_command",
        "id",
        "caption");
    $other_vars = array(
        "tooltip",
        "extra_attributes",
        'div_wrap');

    if (!$extra_attributes)
        $extra_attributes = array();
    elseif (!is_array($extra_attributes))
        $extra_attributes = array($extra_attributes);

    if (is_array($button_id_or_input)) {

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
        $extra_attributes = array_merge($extra_attributes, $button_id_or_input);

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

    $select_mode = ($data_id == JAVASCRIPT_DUMP_ID_PREFIX);
    $is_relay = $return_id == "relay";


    if ($is_relay) {
        $data_id .= "__relay__";
        $return_id = $data_id;
    }
    if (!$return_id || $is_relay)
        $return_id = $data_id;


    $id_start = "{$data_id}_tasmota_cmnd_";
    $return_id_slashed = addslashes($return_id);

    $textbox_js = "tasmota_manual_command('$return_id_slashed','textbox_update',this.value); return true;";

    if ($is_relay)
        $checkbox_js = "tasmota_manual_command('$return_id_slashed','get_reference_relay',this.value); return true;";
    else
        $checkbox_js = "tasmota_manual_command('$return_id_slashed','get_reference',this.value); return true;";

    if ($select_mode)
        $button_js = js_exec_selected("not used", $is_relay, "this.value");
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
        "onclick" => $textbox_js);
    $checkbox_tooltip_attributes = array("class" => "tooltiptext", "id" => $checkbox_tooltip_id);

    $checkbox_attributes = array(
        "type" => "checkbox",
        "id" => $id_start . "checkbox",
        "style" => array("cursor" => "help"),
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
        '</span></span>';
    $html .= make_button($button_attributes);
    return $html;

}
function remove_tasmota_button($hostname, $return_id = false, $caption = "", $tooltip =
    "")
{

    if (SCAN_MODE)
        return "";

    if (!$return_id)
        $return_id = $hostname;

    if (!$tooltip)
        $tooltip = "Remove $hostname from " . list_name();
    if (!$caption)
        $caption = '<img src="' . stored_file("x.png") . '" />';

    $return_id_get = js_format_return_id($return_id);

    $var_append_array = array(
        "return_id" => $return_id_get,
        "remove_hostname" => $hostname,
        "list_name" => list_name());


    foreach ($var_append_array as $key => $value)
        $var_append_array[$key] = "$key=" . urlencode($value);

    $var_append = implode("&", $var_append_array);
    $js_url = JS_PATH . "?$var_append";
    $js_command = "dhtmlLoadScriptAddToQueue('$js_url');";
    $button_id = $return_id . "_remove_hostname_button";
    $tooltip_attrib = ' title="' . $tooltip . '" ';
    $extra_attrib = array();
    $extra_attrib['style']['cursor'] = "help";
    return make_button($button_id, $js_command, $caption, $tooltip, $extra_attrib);


}
function make_select_input($hostname)
{

    $select = '<input type="checkbox" name="hostnames[]" value="' . $hostname .
        '" onclick="add_hostname_to_list(this.value, this.checked)" class="hostname" />';
    $select .= "<br />".make_refresh_icon($hostname);
    $select = '<div class="wrap_select_checkbox">' . $select . '</div> ';
    return $select;
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
        case "tasmota_command_input";
        case "other":
            $class = "tasmota_command_input";
            break;
        default:
            $ref = tasmota_has_reference($value_name, "full", $value);
            if ($ref)
                $value = $ref;
            break;
        case "tiny_js_output":
            $class = "tasmota_tiny_result";
        case "js_output":
            $json_div = "";

            break;
        case 'ipaddress':
        case 'ip_address':
            $id = " id=\"{$hostname}_td_ip_address_string\" ";
            $html = '<a  ' . $id . ' target="_blank" href="http://' . "$device_username:$device_password@$value" .
                '/">' . $value . '</a>';
            $html .= '&nbsp;&nbsp;<span><a href="' . single_item_url($value) . '">';

            $html .= '<img src="' . stored_file("search_icon.png") . '" /></a>';
            $html .= '<span class="tooltiptext">Isolate Item</span></span>';
            $value = $html;
            break;
        case 'hostname':
            $id = " id=\"{$hostname}_td_hostname_string\" ";
            $value = '<a  ' . $id . ' target="_blank" href="http://' . "$device_username:$device_password@$value" .
                '/">' . $value . '</a>';
            break;
        case 'power':
        case 'power1':
        case 'power2':
        case 'power3':
        case 'power4':
        case 'power5':
        case 'power6':
        case 'power7':
        case 'power8':
        case 'power9':
        case 'power10':
        case 'power11':
        case 'power12':
            $relay_num = (int)filter_var($value_name, FILTER_SANITIZE_NUMBER_INT);
            if (!$relay_num)
                $relay_num = 1;
            $value = create_relay_switch($value, $relay_num, "", $hostname, $hostname);

            break;
        case 'tiny_relays';
            $relay_select_mode = ($hostname == "selected" || $value == "selected");
            if ($relay_select_mode)
                return format_display_value($value, $display_name, $hostname, "relayname");

            if (is_array($value) && $value) {
                $disp_var = "POWER";
                $total_relays = count($value);
                $html = "";
                $attrib = "";
                $html .= "<table class=\"tasmota_tiny_relay_table\">";
                $row1 = array();
                $row2 = array();
                foreach ($value as $relay_number => $name) {

                    $relay_number++;
                    $result_id = "{$hostname}_td_$disp_var$relay_number";
                    $relay_class = "tasmota_relay_checkbox";
                    $relay_checkbox_attrib_array = array(
                        "tag_name" => "input",
                        "type" => 'checkbox',
                        "class" => $relay_class);
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
                    $relay_checkbox_td_attrib = array("class" => "tasmota_tiny_relay_select");
                    $contents = create_element(false, $relay_checkbox_attrib_array);
                    $contents = "<span>$contents<span class=\"tooltiptext\">Select $name</span></span>";
                    $row1_td = create_td($contents, $relay_checkbox_td_attrib);


                    $relay_results_attrib = array("id" => $result_id, "class" =>
                            "tasmota_tiny_relay_result");

                    $contents = create_relay_switch("switch", $relay_number, $name, $hostname, $hostname);
                    $row2_td = create_td($contents, $relay_results_attrib);
                    $row1[] = $row1_td;
                    $row2[] = $row2_td;

                }
                $html .= "<tr>" . implode("", $row1) . "</tr>";
                $html .= "<tr>" . implode("", $row2) . "</tr>";
                $html .= "</table>";
                $value = $html;

            } else
                $value = "[No Relays]";


            break;
        case 'relayname':
            $relay_select_mode = ($hostname == "selected" || $value == "selected");
            if ($relay_select_mode)
                $value = array(RELAY_PLACEHOLDER => "Selected Relays");
            elseif (LIST_DISPLAY_MODE == "short")
                return format_display_value($value, $display_name, $hostname, "tiny_relays");
            if (is_array($value) && $value) {
                $td_attrib_index_array = TASMOTA_RELAY_TABLE_TD_INDEX_ATTRIBUTES;

                $td_power_attrib_array = TASMOTA_RELAY_TABLE_TD_POWER_ATTRIBUTES;
                $td_power_result_attrib_array = TASMOTA_RELAY_TABLE_TD_POWER_RESULT_ATTRIBUTES;
                $td_result_attrib_array = TASMOTA_RELAY_TABLE_TD_RESULT_ATTRIBUTES;

                if ($relay_select_mode) {
                    $table_attrib_array = TASMOTA_RELAY_TABLE_SELECT_ATTRIBUTES;
                    $th_attrib_array = TASMOTA_RELAY_TABLE_SELECT_TH_ATTRIBUTES;
                    $td_attrib_array = TASMOTA_RELAY_TABLE_SELECT_TD_ATTRIBUTES;
                } else {
                    $table_attrib_array = TASMOTA_RELAY_TABLE_ATTRIBUTES;
                    $th_attrib_array = TASMOTA_RELAY_TABLE_TH_ATTRIBUTES;
                    $td_attrib_array = TASMOTA_RELAY_TABLE_TD_ATTRIBUTES;
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
                        //[num]
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
                            $button = command_button_selected($cmnd, $caption, false, false, true);
                        } else {
                            $caption = "$disp_var";
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
                        $power_row = (strtolower(trim($index)) == "power") && $result_id;

                        $td_index_attrib = create_element_attributes($td_attrib_index_array);
                        $html .= "<tr><td $td_index_attrib><b>$index</b></td></tr>";
                        if ($power_row)
                            $td_attrib = create_element_attributes($td_power_attrib_array);
                        else
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
                        $html .= "</div></td>";

                        $id = array("id" => $result_id);
                        if ($power_row)
                            $td_result_attrib = create_element_attributes($td_power_result_attrib_array, $id);
                        else
                            $td_result_attrib = create_element_attributes($td_result_attrib_array, $id);

                        if ($result_id && $power_row)
                            $html .= "<td $td_result_attrib><div class=\"$class\">" . create_relay_switch("switch",
                                $relay_number, $name, $hostname, $hostname) . "</div></td>";
                        elseif ($result_id)
                            $html .= "</tr><tr><td $td_result_attrib>&nbsp;</td></tr>";
                    }


                }
                if ($relay_select_mode) {

                    $td_index_attrib = create_element_attributes($td_attrib_index_array);
                    $html .= "<tr><td $td_index_attrib><b>COMMAND</b></td></tr>";
                    $html .= "<tr><td $td_index_attrib>Note: Will ignore specified relay number(s)</td></tr>";
                    $td_attrib = create_element_attributes($td_attrib_array);

                    $html .= "<tr><td $td_attrib><div class=\"tasmota_command_input\">";
                    $html .= make_tasmota_cmnd_input(JAVASCRIPT_DUMP_ID_PREFIX, "relay");
                    $html .= "</div></td><tr>";
                }
                $html .= "</table>";
                $value = $html;
            } else
                $value = "[No Relays]";
            break;

        case "mqtthost":
            $mqtt_hosts = MQTT_HOSTS;
            if (isset($mqtt_hosts[$value]))
                $value = $mqtt_hosts[$value];
        case "select":
            return $value;

    }
    $value = '<div class="' . $class . '">' . smart_nl2br($value) . '</div>' . $json_div;
    return $value;
}
function make_refresh_icon($data_id, $return_id=false, $cmnd = false, $close_popup=false)
{
    if(!$return_id)
        $return_id = $data_id;
        
    if($cmnd)
    {
        $tooltip = "Refresh ".construct_command_string($cmnd);
    }
    else
    {
        $cmnd = DEFAULT_CMND;
        $tooltip = "Refresh $data_id";
        
    }
    $js_command = js_exec_command($cmnd,$data_id,$return_id,false,true);
    $img_src = stored_file("refresh.png");
    $attrib = array();
    $attrib['src'] = $img_src;
    $attrib['onclick'] = $js_command;
    $attrib['style']['cursor'] = "pointer";
    $img = create_img($attrib);

    $html = '<span>' . $img . '<span class="tooltiptext">' . $tooltip .
        '</span></span>';
    return $html;
}
function create_relay_switch($value, $relay_num, $friendly_name, $data_id, $return_id = false,
    $close_popup = false)
{
    if ($friendly_name)
        $friendly_name .= "<br />";
    else
        $friendly_name = "";
    $img_off = stored_file("off.png");
    $img_on = stored_file("on.png");
    $img_switch = stored_file("switch.png");
    if (is_numeric($value))
        $value = $value ? "on" : "off";

    if (is_string($value))
        $value = strtolower($value);
    $selected_mode = ($data_id == "selected");

    switch ($value) {
        case "selected":
            $selected_mode = true;
        case "start":
        case "switch":
            $cmnd = "POWER$relay_num";
            $img_src = $img_switch;
            $tooltip = "<b>Click to get relay $relay_num state</b><br />$friendly_name";
            break;
        case "on":
            $cmnd = "POWER$relay_num 0";
            $img_src = $img_on;
            $tooltip = "<i>Relay $relay_num is ON </i><br />$friendly_name Click to turn off";
            break;
        case "off":
            $cmnd = "POWER$relay_num 1";
            $img_src = $img_off;
            $tooltip = "<i>Relay $relay_num is OFF</i><br />$friendly_name Click to turn on";
            break;
        default:
            return "?";
    }

    if ($selected_mode)
        $js_command = js_exec_selected($cmnd, true);
    else
        $js_command = js_exec_command($cmnd, $data_id, $return_id);
    if ($close_popup)
        $js_command .= " collapse_warnings();";
    $attrib = array();
    $attrib['src'] = $img_src;
    $attrib['onclick'] = $js_command;
    $attrib['style']['cursor'] = "pointer";
    $img = create_img($attrib);

    $html = '<span>' . $img . '<span class="tooltiptext">' . $tooltip .
        '</span></span>';
    return $html;


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
function stored_file($filename)
{
    return EXTENSIONS_DIRECTORY . "/tasmota_functions/$filename";
}
function smart_nl2br($text, $limit_lines = 50000, $truncate_and_strip = false)
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
    if ($limit_lines != 50000 || $truncate_and_strip) {
        $lines = explode("<br />", $text);
        $text = "";
        $new_lines = array();
        for ($i = 0; $i < $limit_lines && isset($lines[$i]); $i++) {
            $line = $lines[$i];
            if ($truncate_and_strip) {
                $line = strip_tags($line);
                $new_line = substr($line, 0, $truncate_and_strip);
                if (strlen($new_line) != strlen($line)) {
                    $new_line = substr($line, 0, $truncate_and_strip - 3);
                    $new_line .= "...";
                }
                $line = $new_line;
            }
            $new_lines[$i] = $line;
        }
        $text = implode("<br />", $new_lines);
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


function dump_img($input1 = false, $input2 = false, $input3 = false, $input4 = false,
    $input5 = false)
{
    $always_tagname = "img";
    echo create_element(false, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function dump_td($contents, $input1 = false, $input2 = false, $input3 = false, $input4 = false,
    $input5 = false)
{
    $always_tagname = "td";
    echo dump_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function dump_div($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false)
{
    $always_tagname = "div";
    echo dump_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function dump_th($contents, $input1 = false, $input2 = false, $input3 = false, $input4 = false,
    $input5 = false)
{
    $always_tagname = "th";
    echo dump_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);
}

function dump_element($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false, $always_tagname = false)
{
    $html = create_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);
    echo $html;

}


function create_img($input1 = false, $input2 = false, $input3 = false, $input4 = false,
    $input5 = false)
{
    $always_tagname = "img";
    return create_element(false, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function create_td($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false)
{
    $always_tagname = "td";
    return create_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function create_div($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false)
{
    $always_tagname = "div";
    return create_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);

}
function create_th($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false)
{
    $always_tagname = "th";
    return create_element($contents, $input1, $input2, $input3, $input4, $input5, $always_tagname);
}

// welcome to my stupid idea: create a repository of ALL the attributes of divs and ths and tds
// if an array is given as an input, it'll just assume its the attribute array
// if strings are given, it'll look up those as keys'
function create_element($contents, $input1 = false, $input2 = false, $input3 = false,
    $input4 = false, $input5 = false, $always_tagname = false)
{

    $attrib = array();
    if ($always_tagname)
        $attrib['tag_name'] = $always_tagname;
    if (is_array($contents))
        $contents = smart_nl2br($contents);
    $inputs = $search_input = array(
        $input1,
        $input2,
        $input3,
        $input4,
        $input5);
    $search_output = array();
    $data_array = ELEMENT_SETTINGS;

    $found_attributes = search_saved_attributes($search_input, $data_array, $search_output);
    $attrib = array_merge($found_attributes, $attrib);
    $tag = isset($attrib['tag_name']) ? $attrib['tag_name'] : "td";
    $attrib_string = create_element_attributes($attrib);
    if ($contents === false)
        return "<$tag $attrib_string />";
    else
        return "<$tag $attrib_string>$contents</$tag>";
}
function search_saved_attributes(&$input, &$cur_array, &$output)
{

    $output[] = array("input" => $input, "cur_array" => $cur_array);
    $return = "";
    $error = "[Error not defined.. ]";
    while (true) {

        // clear out false
        if (!isset($output['initialized'])) {
            $output['initialized'] = true;
            $clean_input = array();
            foreach ($input as $input_num => $i) {
                if ($i !== false)
                    $clean_input[] = $i;
                if ($i === "") {
                    $error = "input number $input_num was a blank string";
                    break;
                }
                if (!is_numeric($i) || !is_string($i)) {
                    $error = "input number $input_num was an invalid data type...";
                    break;

                }

            }

            $input = $clean_input;
        }

        $test = array_shift($input);
        // apparently one of the inputs was an array, I guess that's cool, we'll assume it's what we want
        if (is_array($test))
            return $test;

        if (!is_array($cur_array)) {
            $error = "coding error: cur array is not array, it should never have gone this far!";
            break;
        }

        if (isset($cur_array[$test])) {
            $cur = $cur_array[$test];
            // cur array is not done
            if (is_array($cur)) {
                // input is done!, $test must be our value
                // however, data array wants to keep going
                // lets ignore $test and check if there's a default value'
                if (!$input) {
                    if (isset($cur['default'])) {
                        if (is_array($cur['default'])) {
                            $error = "Data Error, expected default to be string";
                            break;
                        }
                        $return = $cur['default'];
                        break;
                    }
                    $error = "input error: stopped at test $test but data array wanted to keep going.";
                    $error .= " no default value set at this level";
                    break;
                }
                return search_saved_attributes($input, $cur, $output);
            }
            $return = $cur;
            break;
            // gonna deprecate this
            // input wants to keep going but data array has ended
            // invalid key is requested and there is no default value
            if ($input) {
                $error = "input error: invalid key requested: $test";
                break;
            }

        } else {
            if (defined($test)) {
                // well that's not how this is supposed to work.. you gave a constant name as an input'
                $return = $test;
                break;
            }
            if (isset($cur_array['default'])) {
                $return = $cur_array['default'];
                break;
            }
            $error = "test variable not found and no default set at this level";
            break;
        }

        $error .= "here be dragons.. not sure what happened, an error should have been thrown";
        break;

    }
    if ($return) {
        if (defined($return))
            return $return;
        $error = "SO CLOSE! $return not defined constant";
    }
    $output['test variable'] = isset($test) ? $test : "[NOT SET]";
    $output = array_reverse($output, false);
    die("search_saved_attributes $error, Traceback: " . smart_nl2br($output));


}
?>