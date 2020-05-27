<?php
/**
 * This is to display the interfaces to edit the configuration for parsing
 * and other things. 

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/

defined("IN_YAML_HELPER") || die("not in helper");
$echo_saved_page_table_columns = 0;
$echo_saved_page_table_started = false;
function echo_saved_page()
{
    global $echo_saved_page_table_started;
    // true = user setable
    // 1 = user setable and can go into recommended
    // false is not user setable
    // [0] = include in recommended, 1 === RECOMMENDED itself
    // [1] = allow key edit/label
    // [2] = value label
    // [3] = help
    ihide("mode", "parse_settings");
    echo_submit_and_go();
    echo '<hr width="200" align="left" />';
    echo_saved_page_item_helper_submit();
    foreach (USER_SETABLE_CONFIGS as $variable_name => $scheme) {
        if (!is_array($scheme))
            die(" cannot echo setable config $variable_name, invalid scheme:" . var_export($scheme, true));

        foreach (SETTINGS_TEMPLATE_WITH_VALUE as $scheme_key => $make_var_name) {
            if (!isset($scheme[$scheme_key]))
                die("Scheme key '$scheme_key' not set in scheme for $variable_name " .
                    var_export($scheme, true));
            $$make_var_name = $scheme[$scheme_key];
        }
        $help_file = "configure_$variable";
        $help_file .= $recommended === true ? "/configure_all_integrations" : "";
        $can_edit = ($recommended !== 0 && $is_array && !$generated_value);

        if ($generated_value) {
            if ($variable != 'disabled_item_list')
                continue;
            echo_saved_page_table_start($variable, false);
            $prev_item_variable = "";

            foreach ($value as $item_data) {
                $item_value = $item_data['value'];
                $item_warning = $item_data['warning'];
                $item_variable = $item_data['type'];
                $item_key = $item_data['key'];

                $pretty_key = config_format_key($item_key, $variable);
                if (!$item_key || is_numeric($item_key))
                    $item_key = "";
                $send_value = "$item_key@$item_value";
                $pv_name = CONFIG_POST_PREFIX . $item_variable . "[]";

                $item_key_label = get_config_scheme($item_variable, "key_label");
                $item_key_label || $item_key_label = "";
                $item_value_label = get_config_scheme($item_variable, "value_label");
                $item_help = get_config_scheme($item_variable, "help");
                if (strpos($item_value, "array_") === 0)
                    $pretty_value = "<b>All</b> " . get_config_scheme(substr($item_value, 6), "help");
                else
                    $pretty_value = $item_value;

                if ($prev_item_variable != $item_variable) {
                    echo '<tr><th colspan="3">';
                    echo $item_help;
                    echo '</th></tr>';
                    echo '<tr><th>Enable</th>';
                    if ($item_key_label)
                        echo '<th>' . $item_key_label . '</th><th>';
                    else
                        echo '<th colspan="2">';
                    echo "$item_value_label</th></tr>";
                }
                $prev_item_variable = $item_variable;

                echo '<tr><td>';
                icheckbox($pv_name, $send_value, false, "", $item_warning);
                echo '</td>';
                if ($item_key_label)
                    echo '<td>' . $pretty_key . "</td><td>";
                else
                    echo '<td colspan="2">';

                echo "$pretty_value";

                echo '</td></tr>';
                if ($item_warning)
                    echo '<tr><td><b>* Warning:</b></td><td colspan="2">' . "$item_warning</td></tr>";


            }
            echo '<tr><td colspan="3">';

            $submit_pv_name = CONFIG_POST_PREFIX . "save";

            $multivar = array($submit_pv_name => "all", 'jump_to_onload' => $help);

            isubmit_multi($multivar, "Save $help", "Save $help will save ALL settings on the page.");
            echo '</td></tr>';

            echo_saved_page_table_end();
            continue;
        }
        if ($recommended === 0 && isset($scheme['arrays'])) {
            $arrays = $scheme['arrays'];
        } else
            $arrays = array();
        $col1_text = $can_edit ? "Enable / Edit" : "Enable";

        if (!$is_array) {
            if(!$echo_saved_page_table_started)
                echo_saved_page_table_start(array('help'=>"Misc. Settings","value_label"=>"Misc"), false);
                
            echo_saved_page_table_start($variable, false);
            echo "<tr><td>";
            if ($is_string)
                itext(CONFIG_POST_PREFIX . $variable, $value, "$value_label", "(REQUIRED)");
            elseif ($is_bool)
                icheckbox(CONFIG_POST_PREFIX . $variable, 1, $value, $help);
            else
                die("unknown config type for $variable in echo saved page");
            echo "</td></tr>";

            echo_saved_page_item_helper_submit($variable,"button_list");
            continue;
        }
        $list_items = array();

        echo_saved_page_table_start($variable, $col1_text);
        //echo $arrays ? "Array found<br />" : "no array found<br />";

        foreach ($arrays as $array_name => $array_scheme) {
            $ar_help = $array_scheme[3];
            $ar_pv = "array_$array_name";

            $found_key = array_search($ar_pv, $value);
            // prop not needed
            $item_key = $ar_pv;
            $item_value = "<b>All</b> " . $array_scheme[3];
            if ($found_key !== false) {

                $checked = true;
                // probably not necessary
                unset($value[$found_key]);
            } else {
                $checked = false;
                $list_items += $array_scheme['value'];
                continue;

            }
            echo_saved_page_item_helper_checkbox_KEY($variable, $item_key, $item_value, $checked);
        }
        if ($list_items) {
            sort($list_items);
            $list_items = array(" " => " ") + $list_items;
            echo_saved_page_item_helper_select($variable, "", "", $list_items, "value", " ");
        }

        foreach ($value as $item_key => $item_value) {
            if (is_numeric($item_key)) {
                $item_key = "";
                if ($key_required)
                    $item_key = "[ERROR: NOT SET]";
            }

            echo_saved_page_item_helper_checkbox($variable, $item_key, $item_value, true, false,
                $can_edit);
        }

        if ($recommended !== 0) {
            $fillin_key = $fillin_value = "";

            if ($ignore_list = pv_or_else(CONFIG_POST_PREFIX . $variable . "_edit", array())) {
                echo "found ignore";
                $ex = explode("@", $ignore_list[0]);
                list($fillin_key, $fillin_value) = count($ex) == 2 ? $ex : array("", $ex);
            }
            echo_saved_page_item_helper_text($variable, $fillin_key, $fillin_value, $key_required);
        }


        echo_saved_page_item_helper_submit($variable);
        //isubmit($submit_pv_name, "$variable", "Save $help", "Save $help and discard changes to other settings?");
        echo_saved_page_table_end();
    }
    
            echo_saved_page_table_end();
    echo '<hr width="400" align="left" />';
    echo_saved_page_item_helper_submit();
    echo '<hr width="200" align="left" />';
    echo_submit_and_go();
    if ($errors_string = parse_error()) {
        popup_msg_on_load($errors_string, "Configuration Errors/Warnings");
    }
}
function echo_saved_add_missing_list_scheme($entities)
{
    if (!(pv_or_blank("new_parse_input_mode") == 'ask_for_missing_list_scheme'))
        help_on_load("ask_for_missing_list_scheme", "List Name Scheme Not Found");

    $variable = 'list_name_scheme';
    echo_saved_page_table_start($variable, 'Item Num');
    foreach ($entities as $item_num => $entity) {
        $item_num++;
        if ($entity['list_scheme_found'])
            continue;
        $keys = array();
        $values = array();
        $checked_key = false;
        $checked_value = false;
        foreach ($entity['fields_formatted'] as $field => $value) {
            $string = "$field:$value";
            if (!$checked_key && $field == "platform")
                $checked_key = $string;
            if ($field == "integration_name" && strpos($value, "/"))
                $checked_key = $string;
            $keys[$string] = format_list_scheme_key($string);

            if ($field == "name" || $field == "alias")
                $checked_value = $field;
            $string = "$field";
            $values[$string] = format_list_scheme_value($string) . "<br /><i>Ex. $value</i>";
        }

        echo "<tr><td>";
        $msg = "";
        $entity_contents = array();
        parse_error_array($entity_contents, $entity);
        $msg .= "Entity #$item_num could not be named because my poor little program could not figure out how to name it.\n";
        $msg .= "Here you must build a scheme by which to extract a name.\n";
        $msg .= "This is really just so we can construct a file name, as homeassistant is going to do whatever it wants. Typically using 'alias' or 'name'.\n";
        $msg .= "Here is the contents the parser was able to extract:.\n";
        $msg .= string_to_pre(implode("", $entity_contents));
        $msg = $entity;
        popup_button("#$item_num Contents", $msg, true, "button_list");
        echo "</td>";
        echo "<td>";
        iradio_array(CONFIG_POST_PREFIX . $variable . "_key", $keys, $checked_key);
        echo "</td>";
        echo "<td>";
        iradio_array(CONFIG_POST_PREFIX . $variable . "_value", $values, $checked_value,
            CONFIG_POST_PREFIX . $variable . "_append_to_value[]",
            "<b>Append to end of name with [AND]</b>");
        echo "</td></tr>";
        break;
    }

    $submit_pv_name = CONFIG_POST_PREFIX . "append";
    ihide_pv("disp_mode", "view_all");
    ihide_pv('integration', "configuration");
    ihide_pv('parse_mode');
    ihide("new_parse_input_mode", "ask_for_missing");

    echo_saved_page_table_end();
    isubmit($submit_pv_name, "all", "Save Settings", "Are you sure about this?");

}
function echo_saved_add_missing($ask_for_missing)
{
    if (!(pv_or_blank("new_parse_input_mode") == 'ask_for_missing'))
        help_on_load("ask_for_missing", "Integrations Not Found");
    $table_headers = array(
        "name" => "unspecified_items",
        "key_label" => "Type",
        "value_label" => "Entities Location<br />(Optional)",
        "help" => "Specify Item Details",
        "help_file" => "parse_specify_item_details",
        "key_wildcards" => false);

    echo_saved_page_table_start($table_headers, "Item Name");
    $types = array(
        "dict" => "dictionary_integrations",
        "list" => "list_integrations",
        "setting" => "settings_integrations");
    $nested_types = array("dict" => "nested_dict", "list" => "nested_list");
    foreach ($ask_for_missing as $name => $data) {
        $type = isset($data['type']) ? $data['type'] : false;
        $ask_location = $data['ask_location'];
        $is_nested = strpos($name, "/") !== false;
        $possible_types = $is_nested ? $nested_types : $types;
        $input_name_prefix = ""; // $v
        echo "<tr><td>";
        echo "<b>$name</b>";
        echo '</td><td>';
        if ($type) {
            if (!isset($possible_types[$type]))
                die("echo saved page missing - type $type not recognized...");

            $pretty_key = config_format_key($type, $possible_types[$type]);
            $input_name_prefix = CONFIG_POST_PREFIX . $possible_types[$type];
            ihide($input_name_prefix . "_value[]", $name);
            $pretty_name = get_config_scheme($possible_types[$type], 'help');
            echo "<b>$pretty_name</b>";
            $ask_location = $data['path'];

        } else {
            $selected_type = false;
            if (!$is_nested)
                $selected_type = 'settings_integrations'; // can be setting_integrations, dictionary_integrations,list_integrations

            $ihide_default_name = ($selected_type ? CONFIG_POST_PREFIX . $selected_type .
                "_value[]" : "");

            ihide($ihide_default_name, $name, $name . "_value");
            echo '<select onchange="echo_saved_change_names(this,\'' . $name . '\');">';
            echo '<option disabled ' . ($selected_type ? '' : 'selected') .
                '>-- SELECT TYPE --</option>';
            foreach ($possible_types as $this_type) {
                $select_this = ($selected_type == $this_type ? "selected" : "");
                if ($is_nested)
                    $pretty_type = str_replace("_", " ", $this_type);
                else
                    list($pretty_type, $wutever) = explode("_", $this_type, 2);
                $pretty_type .= ($select_this ? "*" : "");
                $pretty_type = ucwords($pretty_type);
                echo '<option value="' . CONFIG_POST_PREFIX . $this_type . '" ' . $select_this .
                    '>' . $pretty_type . '</option>';
            }
            echo '</select>';
        }
        echo "</td>";
        echo "<td>";
        if ($ask_location) {
            $string = is_string($ask_location) ? $ask_location : "";
            $placeholder = $string ? "(Original: $string)" : ("(OPTIONAL)");
            $key_wildcards = "";
            if ($type) {
                $key_text_id = itext($input_name_prefix . "_key[]", $string, "", $placeholder);
                $key_wildcards = get_config_scheme($possible_types[$type], 'key_wildcards');
            } else {
                if ($is_nested)
                    $key_wildcards = get_config_scheme($possible_types['dict'], 'key_wildcards');
                $key_text_id = itext("", $string, "", $placeholder, $name . "_key");
            }
            if ($key_wildcards)
                echo_saved_page_key_wildcards($key_text_id, $key_wildcards);
        } else {
            $pretty_default = "";

            config_format_key($pretty_default, $input_name_prefix);
            echo "$pretty_default";
            if ($type) {
                ihide($input_name_prefix . "_key[]", "");
            } else {
                ihide("", "", $name . "_key");
            }

        }

        echo "</td></tr>";
    }
    echo_saved_page_table_end();

    $submit_pv_name = CONFIG_POST_PREFIX . "append";
    ihide_pv("disp_mode", "view_all");
    ihide_pv('integration', "configuration");
    ihide_pv('parse_mode');
    ihide("new_parse_input_mode", "ask_for_missing");
    isubmit($submit_pv_name, "all", "Save Settings", "Are you sure about this?");

}
function echo_saved_page_item_helper_submit($input = array(),$class=false)
{
    global $echo_saved_page_table_columns;
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }
    $submit_pv_name = CONFIG_POST_PREFIX . "save";

    $reset_pv_name = CONFIG_POST_PREFIX . "reset";
    $default_pv_name = CONFIG_POST_PREFIX . "default";
    if ($variable) {
        echo '<tr><td colspan="' . $echo_saved_page_table_columns . '">';

        $multivar = array($submit_pv_name => $variable, 'jump_to_onload' => $help);

        isubmit_multi($multivar, "Save $help", "Save $help and discard changes to other settings?",$class);

        //isubmit($submit_pv_name, "$variable", "Save $help", "Save $help and discard changes to other settings?");
        echo " ";
        //isubmit($reset_pv_name, "$variable", "Reset", "Reset $help to Saved? Will save the rest?");

        $multivar = array($reset_pv_name => $variable, 'jump_to_onload' => $help);
        isubmit_multi($multivar, "Reset", "Reset $help to Saved? Will save the rest?",$class);
        echo " ";
        $multivar = array($default_pv_name => $variable, 'jump_to_onload' => $help);

        //isubmit($default_pv_name, "$variable", "Default", "Set $help to defaults? Will save the rest?");

        isubmit_multi($multivar, "Default", "Set $help to defaults? Will save the rest.",$class);
        echo '</td></tr>';
    } else {
        $just_one = true;
        switch ($key_label) {
            default:
                $just_one = false;
            case 'save':
                $save_label = $value_label ? $value_label : "Save All";
                isubmit($submit_pv_name, "all", $save_label,false,$class);
                if ($just_one)
                    break;
                echo " ";
            case 'reset':
                $reset_label = $value_label ? $value_label : "Reset All";
                isubmit($reset_pv_name, "all", $reset_label, "Reset all settings to saved?",$class);
                if ($just_one)
                    break;
                echo " ";
            case 'default':
                $default_label = $value_label ? $value_label : "Default";
                isubmit($default_pv_name, "all", $default_label, "Set all to defaults?",$class);
                break;


        }
    }


}

function echo_submit_and_go()
{
    $submit_pv_name = CONFIG_POST_PREFIX . "save";
    $multivar = array($submit_pv_name => 'all', 'new_mode' => 'parse_input');

    isubmit_multi($multivar, "Save & Parse Input",
        "Save all changes and go to parse input?");
}
function echo_saved_page_table_start($input, $col1_text = "Enable")
{
    global $echo_saved_page_table_columns;
    global $echo_saved_page_table_started;
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }
    $extra_columns = isset($input['extra_columns']) ? $input['extra_columns'] : 0;


    $cols = ($key_label ? 1 : 0) + ($value_label ? 1 : 0) + ($key_wildcards ? 1 : 0) +
        ($col1_text ? 1 : 0) + $extra_columns;
    $cols = $cols?$cols:1;
    $echo_saved_page_table_columns = $cols;
    $class = "";
    switch ($cols) {
        case 0:
            die("no stairway, denied! echo saved page table start");
            break;
        case 1:
            $class = ' class="one_col_config" ';
            break;
        case 2:
            $class = ' class="two_col_config" ';
            break;
        default:
            $class = ' class="multi_col_config" ';
            break;

    }
    if(!$echo_saved_page_table_started)
    {
        echo "\n\n<hr width=\"400\" align=\"left\" />";
        echo "<table $class id=\"$help\">\n";
        echo '<tr><th class="config_name" colspan="' . $cols . '">';
        echo $help;
        if($help_file)
        help_link($help_file, $help);
        echo "</th></tr>";
        
    }
    else
    {
        echo '<tr><th id="'.$help.'" align="left" colspan="' . $cols . '">';
        echo $help;
        if($help_file)
        help_link($help_file, $help);
        echo "</th></tr>";
        
    }
    $echo_saved_page_table_started=true;

    if ($cols == 1)
        return;
    if ($col1_text === false)
        return;

    echo "<tr><th>$col1_text</th>";
    if ($key_label && $key_wildcards)
        echo "<th colspan=\"2\">$key_label</th>";
    elseif ($key_label)
        echo "<th>$key_label</th>";
    if ($value_label)
        echo "<th>$value_label</th>";
    echo "</tr>\n";

}

function echo_saved_page_table_end()
{
    global $echo_saved_page_table_started;
    if(!$echo_saved_page_table_started)
        return false;
    $echo_saved_page_table_started = false;
    echo "\n</table>\n";

}

function echo_saved_page_item_helper_checkbox_KEY($input, $item_key, $item_value,
    $checked, $ihide = false)
{
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }

    $pretty_key = config_format_key($item_key, $variable);

    $pv_name = CONFIG_POST_PREFIX . $variable . "[]";
    $check_value = $value_label && $key_label ? "$item_key@$item_value" : ($item_key);
    if ($ihide) {
        if (!$checked)
            return;
        ihide($pv_name, $check_value);
        return;
    }
    echo "<tr>";
    echo "<td>";
    icheckbox($pv_name, $check_value, $checked, "");
    echo "</td>";


    if ($key_label && $key_wildcards)
        echo "<td colspan=\"2\">$pretty_key</th>";
    elseif ($key_label)
        echo "<td>$pretty_key</th>";

    if ($value_label)
        echo "<td>$item_value</td>";


    echo "</tr>";
}

function echo_saved_page_item_helper_checkbox($input, $item_key, $item_value, $checked,
    $ihide = false, $can_edit = false)
{
    global $echo_saved_page_table_columns;
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }

    $pretty_key = config_format_key($item_key, $variable);
    $pretty_value = config_format_value($item_value, $variable);
    $pv_name = CONFIG_POST_PREFIX . $variable . "[]";
    $check_value = $value_label && $key_label ? "$item_key@$item_value" : ($item_value);
    if ($ihide) {
        if (!$checked)
            return;
        ihide($pv_name, $check_value);
        return;
    }
    echo "<tr>";
    echo "<td>";
    icheckbox($pv_name, $check_value, $checked, "");

    if ($can_edit) {

        $submit_pv_name = CONFIG_POST_PREFIX . "save";

        $edit_pv_name = CONFIG_POST_PREFIX . $variable . "_edit[]";
        $multivar = array(
            $submit_pv_name => $variable,
            $edit_pv_name => $check_value,
            'jump_to_onload' => $help);


        isubmit_multi($multivar, "Edit",
            "This will disable the current item (and save this setting) to allow you to edit it.",
            "button_list");
    }


    echo "</td>";


    if ($key_label && $key_wildcards)
        echo "<td colspan=\"2\">$pretty_key</th>";
    elseif ($key_label)
        echo "<td>$pretty_key</th>";
    if ($value_label)
        echo "<td>$pretty_value</td>";


    echo "</tr>";
}

function echo_saved_page_item_helper_text($input, $item_key, $item_value)
{
    global $echo_saved_page_table_columns;
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }

    $value_placeholder = $key_placeholder = '(REQUIRED)';
    if ($key_required) {
        if (!$key_label)
            die("key is required but not for $variable ? make up your mind!");
    } else
        $key_placeholder = '(optional)';
    $pv_name_key = CONFIG_POST_PREFIX . $variable . "_key";
    $pv_name_value = CONFIG_POST_PREFIX . $variable . "_value";


    echo "<tr>";
    echo "<td>";
    //icheckbox($pv_name, $item_value, $checked, "");
    echo "</td>";
    if ($key_label) {
        echo "<td>";
        $itext_id = itext($pv_name_key, $item_key, "", $key_placeholder);

        echo "</td>";

        if ($key_wildcards) {
            echo "<td>";
            echo_saved_page_key_wildcards($itext_id, $key_wildcards);
            echo "</td>";
        }
    }
    if ($value_label) {

        echo "<td>";
        itext($pv_name_value, $item_value, "", $value_placeholder);

        echo "</td>";
    }


    echo "</tr>";
}
function echo_saved_page_key_wildcards($target_id, $key_wildcards)
{
    if (!$key_wildcards)
        return;

    if ($key_wildcards) {
        $restore_value = "onclick=\"restore_text_and_enable('$target_id');\"";
        //$default_value = "onclick=\"insert_text_and_disable('$target_id','');\"";
        $radio_array = array($restore_value => "Custom");
        foreach ($key_wildcards as $wildcard_label => $wildcard_text) {
            $wildcard_value = "onclick=\"insert_text_and_disable('$target_id','$wildcard_text');\"";
            $radio_array[$wildcard_value] = $wildcard_label;
        }
        iradio_array("ignore_garbage_wildcards_$target_id", $radio_array, $restore_value);
    }
}
// mode key/value - use key/value for both, false for wutever
function echo_saved_page_item_helper_select($input, $item_key, $item_value, $values_array,
    $select_mode, $selected_item)
{
    foreach (SETTINGS_TEMPLATE as $config_val) {
        if (is_array($input)) {
            if (isset($input[$config_val]))
                $$config_val = $input[$config_val];
            else
                $$config_val = false;
        } else
            $$config_val = get_config_scheme($input, $config_val);
    }

    $pv_name = CONFIG_POST_PREFIX . $variable . "[]";
    echo "<tr>";
    $order = array();
    if ($key_label) {
        if ($value_label)
            $order = array(
                "select",
                "key",
                "value");
        else
            $order = array("Select:", //"key",
                    "select");
    } else {
        if ($value_label)
            $order = array("Select:", "select" //"value"
                    );
        else
            $order = array("Select:", "key");
    }
    foreach ($order as $case) {

        echo "<td>";
        switch ($case) {
            case "key":
                echo $item_key;
                break;
            case "select":
                iselect($pv_name, $values_array, $selected_item, "", $select_mode);
                break;
            case "value":
                echo $item_value;
                break;
            default:
                echo $case;
                break;

        }

        echo "</td>";
    }
    echo "</tr>";
}


?>