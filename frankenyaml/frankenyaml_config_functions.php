<?php
/*
This is what happens, larry.

This is what happens when you think "Wouldn't it be nice if users could edit some settings?"

I wrote a ton of code to make it easier to write more code. It probably takes more memory but guess what?

I DO NOT CARE.

RAM IS CHEAP BABY! DOWN WITH GOVERNMENT!

Comments are for suckers!
- DrDS

*/


defined("IN_YAML_HELPER") || die("not in helper");

define("SETTINGS_TEMPLATE", array(
    0 => "recommended",
    1 => "key_label",
    2 => "value_label",
    3 => "help",
    4 => "key_required",
    5 => "allow_duplicates",
    6 => "generated_value",
    7 => "key_wildcards",
    "name" => "variable",
    "is_bool" => "is_bool",
    "is_string" => "is_string",
    "is_array" => "is_array",
    "help_file" => "help_file"));

define("SETTINGS_TEMPLATE_FLIP", array_flip(SETTINGS_TEMPLATE));

define("SETTINGS_TEMPLATE_WITH_VALUE", array(
    0 => "recommended",
    1 => "key_label",
    2 => "value_label",
    3 => "help",
    4 => "key_required",
    5 => "allow_duplicates",
    6 => "generated_value",
    7 => "key_wildcards",
    "name" => "variable",
    "is_array" => "is_array",
    "is_bool" => "is_bool",
    "is_string" => "is_string",
    "help_file" => "help_file",
    "disabled_values" => "disabled_values",
    "value" => "value",
    "flipped" => "flipped",
    "old_values" => "old_values",
    "saved_value" => "saved_value",
    "default_value" => "default_value",
    "value_source" => "value_source"));

define("SETTINGS_TEMPLATE_WITH_VALUE_FLIP", array_flip(SETTINGS_TEMPLATE_WITH_VALUE));
// bool: required OR DIEEE
define("SETTINGS_TEMPLATE_TO_SAVE", array(
    "value" => true,
    "flipped" => false,
    "additional_values" => false,
    "disabled_values" => false,
    "additional_disabled" => false));


define("CONFIG_POST_PREFIX", "set_config_");
function build_constants()
{
    // false = NOT USER SETABLE
    // [0] = true/1 -> include in recommended, 1 = nest,2=settings, 0 = recommended itself, false = not recommended
    // [1] = allow key edit/label
    // [2] = value label
    // [3] = help
    // [4] = key required
    // [5] = allow duplicate values
    // [6] = generated_value
    // [7] = key_wildcards
    $standard_key_wildcards = array("Save Entities In-Line" =>
            SAVE_IN_INTEGRATION_PATH);
    $default_key_wildcard = array("Default" => "");
    $reserved_integrations = array();
    $user_setable_configs = $all_configs = array(
        'prune_older_than_minutes' => false,
        'nested_configuration' => 'reserved',
        'integrations_location' => array(
            false,
            false,
            "Directory (relative to configuration.yaml)",
            "Default Integrations Location"),
        'entities_location' => array(
            false,
            false,
            "Directory (relative to configuration.yaml)",
            "Default Entities Location"),
        'retain_yaml_comments' => array(
            false,
            false,
            false,
            "Retain yaml Comments"),
        'settings_integrations' => array(
            true,
            '',
            "Integration Name",
            "Settings integrations",
            0),
        'dict_integrations' => array(
            true,
            'Entities Location',
            "Integration Name",
            "Dictionary integrations",
            false,
            false,
            false,
            $default_key_wildcard + $standard_key_wildcards),
        'list_integrations' => array(
            true,
            'Entities Location',
            "Integration Name",
            "List integrations",
            false,
            false,
            false,
            $default_key_wildcard + $standard_key_wildcards),
        // nested must be AFTER list/dict/settings
        'nested_list' => array(
            1,
            'Entities Location',
            "Integration Name/search string",
            "Nested List integrations",
            false,
            false,
            false,
            $default_key_wildcard + $standard_key_wildcards,
            ),
        'nested_dict' => array(
            1,
            'Entities Location',
            "Integration Name/search string",
            "Nested Dictionary integrations",
            false,
            false,
            false,
            $default_key_wildcard + $standard_key_wildcards,
            ),
        'list_name_scheme' => array(
            false,
            'Entity Field : Value',
            "Field value to set as name",
            "Naming scheme for list entities",
            true,
            true),
        'disabled_item_list' => array(
            false,
            "Item type -> Value",
            "Key",
            "Disabled Item List",
            false,
            true,
            true),

        // MUST BE LAST!
        'recommended_integrations' => array(
            0,
            false,
            "Specific Integration / Integration Type",
            "Recommended Integrations"),
        'add_recommended_integrations' => array(
            false,
            false,
            false,
            "Automatically Add Recommended Integrations"),
        'disabled_item_prefix' => array(
            false,
            false,
            "Placed in front of disabled lines so the system can take them back later",
            "Comment Line for Disabled Items"),
        'list_name_concat' => array(
            false,
            false,
            "For Naming list items with [and] in there... may never be used",
            "List Name Concatenation Delimiter"),
        );


    $validate_post = false;
    $has_saved_config = false;
    foreach ($user_setable_configs as $name => $scheme) {
        global $$name;
        if (is_array($scheme)) {
            $all_configs[$name] = &$user_setable_configs[$name];
            $user_setable_configs[$name]['is_array'] = is_array($$name);
            $user_setable_configs[$name]['is_bool'] = is_bool($$name);
            $user_setable_configs[$name]['is_string'] = is_string($$name);
            $user_setable_configs[$name]['name'] = $name;
            for ($i = 4; $i < 10; $i++)
                $user_setable_configs[$name][$i] = isset($scheme[$i]) ? $scheme[$i] : false;


            $user_setable_configs[$name]['help_file'] = "configure_$name";
            $user_setable_configs[$name]['help_file'] .= $scheme[0] === true ?
                "/configure_all_integrations" : "";
            $saved_config = config_pull_from_saved($user_setable_configs[$name]);
            $user_setable_configs[$name]['default_value'] = $$name;
            $user_setable_configs[$name]['saved_value'] = $saved_config ? $saved_config['value'] : false;
            $post_value = config_pull_from_post($user_setable_configs[$name]);
            $user_setable_configs[$name]['value_source'] = "default";
            $user_setable_configs[$name]['disabled_values'] = array();
            if ($post_value !== false) {
                $user_setable_configs[$name]['value_source'] = "post";
                $validate_post = true;
                $$name = $post_value;
            } elseif ($saved_config !== false) {
                $user_setable_configs[$name]['value_source'] = "saved";
                $has_saved_config = true;
                $$name = $saved_config['value'];
                if (isset($saved_config['additional_values']))
                    $user_setable_configs[$name]['additional_values'] = $saved_config['additional_values'];
                if (isset($saved_config['additional_disabled']))
                    $user_setable_configs[$name]['additional_disabled'] = $saved_config['additional_disabled'];
                if (isset($saved_config['disabled_values']))
                    $user_setable_configs[$name]['disabled_values'] = $saved_config['disabled_values'];
            }
            $user_setable_configs[$name]['value'] = $$name;
            $user_setable_configs[$name]['old_values'] = $user_setable_configs[$name]['flipped'] =
                array();
            if ($user_setable_configs[$name][0] !== false && $user_setable_configs[$name]['is_array']) {

                $user_setable_configs[$name]['flipped'] = array_flip($$name);

                if ($user_setable_configs[$name]['default_value'])
                    $user_setable_configs[$name]['old_values'] = array_flip($user_setable_configs[$name]['default_value']);

                if ($user_setable_configs[$name]['saved_value'])
                    $user_setable_configs[$name]['old_values'] = array_merge($user_setable_configs[$name]['old_values'],
                        array_flip($user_setable_configs[$name]['saved_value']));

            }
            if ($scheme[0]) {
                //echo "adding $name to rec int<br />";
                $arr_name = "array_$name";
                $user_setable_configs['recommended_integrations']['disabled_values'][$arr_name] = false;
                $user_setable_configs['recommended_integrations']['arrays'][$name] = $user_setable_configs[$name];
                $user_setable_configs['recommended_integrations']['array_names'][$name] = $arr_name;
            }
        } else {
            $all_configs[$name] = array();
            $all_configs[$name]['value'] = $$name;
            unset($user_setable_configs[$name]);
            switch ($scheme) {
                case false:
                    break;
                case 'reserved':
                    if (!is_array($$name))
                        die("$name is not array as expected for reserved");
                    $all_configs[$name]['flipped'] = array_flip($$name);
                    foreach ($$name as $reserved_int)
                        $reserved_integrations[$reserved_int] = $reserved_int;

                    break;

            }
        }

        unset($$name);
    }
    define("RESERVED_INTEGRATIONS", $reserved_integrations);
    // We must re-validate because the posted values may make things valid
    if ($validate_post)
        config_validate_post_data($user_setable_configs);
    arsort($user_setable_configs);
    // SAVE SCRIPT NEEDS GO GO HERE
    define("USER_SETABLE_CONFIGS", $user_setable_configs);
    if ($validate_post)
        save_configuration();
    set_constants($all_configs);
    // we need to add nested to lists and dicts

}
function set_constants($all_configs)
{
    foreach ($all_configs as $variable => $scheme) {
        if (isset($scheme['additional_values']) && $scheme['additional_values'])
            $scheme['value'] = array_merge($scheme['value'], $scheme['additional_values']);
        if (isset($scheme['additional_disabled']) && $scheme['additional_disabled'])
            $scheme['disabled_values'] = array_merge($scheme['disabled_values'], $scheme['additional_disabled']);

        define(strtoupper($variable), $scheme['value']);

        if (isset($scheme['flipped']))
            define(strtoupper($variable . "_flipped"), $scheme['flipped']);
        if (isset($scheme['disabled_values']))
            define(strtoupper($variable . "_disabled"), $scheme['disabled_values']);
        //parse_error(strtoupper($variable). " -> ",true);
        //parse_error($scheme['value'],true);

    }
}
function set_default_disabled_values(&$user_setable_configs)
{
    foreach ($user_setable_configs['disabled_item_list']['default_value'] as $item_data) {

        $item_value = $item_data['value'];
        $item_warning = $item_data['warning'];
        $item_variable = $item_data['type'];
        $item_key = $item_data['key'];

        if (!isset($user_setable_configs[$item_variable]['old_values'][$item_value]))
            $user_setable_configs[$item_variable]['old_values'][$item_value] = $item_key;
    }
}
function fix_default_disabled_values(&$user_setable_configs)
{

    foreach ($user_setable_configs['disabled_item_list']['default_value'] as $item_data) {

        $item_value = $item_data['value'];
        $item_warning = $item_data['warning'];
        $item_variable = $item_data['type'];
        $item_key = $item_data['key'];

        if (isset($user_setable_configs[$item_variable]['disabled_values'][$item_value])) {
            $user_setable_configs['disabled_item_list']['value'][$item_value] = $item_data;
        }
    }
}
function config_post_validation_rearrangement(&$user_setable_configs)
{
    $disabled_item_list_value = &$user_setable_configs['disabled_item_list']['value'];
    $disabled_item_list_value = array();
    $nest_appends = array(
        "additional_disabled",
        "flipped",
        "additional_values");
    foreach ($user_setable_configs as $name => $scheme) {
        foreach (SETTINGS_TEMPLATE_WITH_VALUE as $scheme_key => $make_var_name) {
            if (!isset($scheme[$scheme_key]))
                die("Scheme key '$scheme_key' not set in scheme for $variable_name " .
                    var_export($scheme, true));
            $$make_var_name = $scheme[$scheme_key];
        }
        if ($generated_value)
            continue;
        $disabled_values = &$user_setable_configs[$name]['disabled_values'];
        $disabled_values = $old_values;
        $set_add = false;
        if ($variable == "nested_list")
            $set_add = "list_integrations";
        if ($variable == "nested_dict")
            $set_add = "dict_integrations";
        //$user_setable_configs[$nested_target]['additional_values'] = array_merge($user_setable_configs[$nested_target]['value'],
        //$value);

        if ($set_add) {
            $target = &$user_setable_configs[$set_add]['additional_disabled'];
            $target = array_merge($target, $disabled_values);
            unset($target);
            $target = &$user_setable_configs[$set_add]['additional_values'];
            $target = array_merge($target, $value);
            unset($target);
            $target = &$user_setable_configs[$set_add]['flipped'];
            $target = array_merge($target, $flipped);
            unset($target);
        }
        foreach ($disabled_values as $item_value => $item_key) {

            $disabled_item_list_value[$item_value] = array(
                "value" => $item_value,
                "type" => $variable,
                "key" => $item_key,
                "warning" => "");

        }
        unset($disabled_values);

    }
    fix_default_disabled_values($user_setable_configs);

}
// validates all post data to make sure it meshes well etc
function config_validate_post_data(&$user_setable_configs)
{
    set_default_disabled_values($user_setable_configs);
    foreach ($user_setable_configs as $name => $scheme) {
        //if ($scheme['value_source'] != 'post')
        //continue;

        foreach (SETTINGS_TEMPLATE_WITH_VALUE as $scheme_key => $make_var_name) {
            if (!isset($scheme[$scheme_key]))
                die("Scheme key '$scheme_key' not set in scheme for $variable_name " .
                    var_export($scheme, true));
            $$make_var_name = $scheme[$scheme_key];
        }
        if ($generated_value)
            continue;
        $value = &$user_setable_configs[$name]['value'];
        if ($is_array) {
            $user_setable_configs[$name]['additional_values'] = array();
            $user_setable_configs[$name]['additional_disabled'] = array();
            foreach ($value as $item_key => $item_value) {


                if (!$allow_duplicates && count($value) > count($scheme['flipped'])) {
                    parse_error("Cannot add duplicate entry in $help");
                    $value = false;
                    break;
                }
                if (is_numeric($item_key) && $key_required) {
                    parse_error("Cannot add $item_value to $help, key is required");
                    $value = false;
                    break;
                }
                $nested_target = "";
                switch ($name) {
                    case 'settings_integrations':
                    case 'dict_integrations':
                    case 'list_integrations':
                        if (in_config_arrays($name, $item_value, $user_setable_configs)) {
                            $value = false;
                            break 2;
                        }
                        break;
                    case 'nested_list':
                    case 'nested_dict':
                        if (strpos($item_value, "/") === false) {
                            $value = false;
                            parse_error("nest needs a / to work, derpydoo");
                            break 2;
                        }
                        list($search, $wutever) = explode("/", $item_value, 2);
                        if (!in_config_arrays($name, $search, $user_setable_configs, false, true)) {
                            parse_error("Removed '$item_value' from $help because Integration $search cannot be found", true);
                            unset($value[$item_key]);
                            break 2;
                        }


                        break;
                    case 'list_name_scheme':
                        $key_error_common = "Cannot set '$item_key' -> '$key_label' in <b>$help</b>,";
                        $ex = explode(":", str_replace(" ", "", $item_key));
                        $labelex = explode(":", $key_label);
                        if (count($ex) != count($labelex)) {
                            parse_error("$key_error_common it must have " . count($labelex) .
                                " semicolon, not " . count($ex));
                            $value = false;
                            break 2;
                        }
                        foreach ($labelex as $i => $this_label) {
                            $ex[$i] = trim($ex[$i]);
                            if (!$ex[$i]) {
                                parse_error("$key_error_common no $this_label");
                                $value = false;
                                break 3;
                            }
                        }
                        $new_key = implode(":", $ex);
                        if ($new_key != $item_key) {
                            parse_error("Just FYI, I changed '$key_label' '$item_key' to '$new_key' in $help", true);
                            unset($value[$item_key]);
                            if (isset($value[$new_key])) {
                                parse_error("Just kidding, $key_error_common '$new_key' is a duplicate");
                                $value = false;
                                break 2;
                            }
                            $value[$new_key] = $item_value;
                        }

                        // ******** NEEDS SOMETHING *********

                        break;
                    case 'recommended_integrations':
                        if (in_array($item_value, $scheme['array_names'])) {
                            $array_name = substr($item_value, 6);
                            $user_setable_configs[$name]['additional_values'] = array_merge($user_setable_configs[$array_name]['value'],
                                $user_setable_configs[$name]['additional_values']);
                            break;
                        }
                        if (!($array_name = in_config_arrays($name, $item_value, $user_setable_configs, false, true))) {
                            parse_error("Cannot add integration '$item_value' to $help, it cannot be found.");
                            $value = false;
                            break 2;
                        }
                        if (in_array("array_$array_name", $value)) {
                            parse_error("<b>$help</b> - Just FYI, '$item_value' was found in {$user_setable_configs[$array_name][3]} which was already in $help. I removed that for you.", true);
                            unset($value[$item_key]);
                        }

                        break;
                    case 'disabled_item_list':
                        parse_error("Uh, disabled_item_list should not be validated..", true);
                        break;
                    default:
                        die("$name cannot be validated, it's not recognized in validate config");
                }

                if (isset($old_values[$item_value]))
                    unset($user_setable_configs[$name]['old_values'][$item_value]);
            }

        } elseif ($is_bool)
            $value = $value ? 1 : 0;
        else //entities location or integraiton location

            ($value = trim($value)) || ($value = false);
        if ($name == 'disabled_item_prefix') {
            if (substr($value, 0, 1) != "#") {
                parse_error("<b>$help</b> - Just FYI, '$value' requires a # before it, let me fix that for you.", true);
                $value = "#$value";
            }
        }

        if ($value === false) {
            parse_error("$help save failed, reverting to saved or default.");
            if (($value = $saved_value) === false)
                $value = $default_value;
        }
        unset($value);
    }
    config_post_validation_rearrangement($user_setable_configs);
}
function in_config_arrays($variable, $search, $user_setable_configs, $error_on_true = true,
    $search_nest = false)
{
    foreach ($user_setable_configs as $name => $scheme) {
        if (!$scheme['is_array'] || $name == $variable || !$scheme[0])
            continue;
        if (!$search_nest && $scheme[0] === 1)
            continue;
        if (isset($scheme['flipped'][$search])) {
            if ($error_on_true)
                parse_error("Error: Duplicate integration '$search' from '$variable' found in $name");
            return $name;
        }

    }
    $res = RESERVED_INTEGRATIONS;
    if (isset($res[$search])) {
        if ($error_on_true)
            parse_error("Error: '$search' is RESERVED.");
        return $name;
    }

    return false;
}
function save_configuration()
{
    global $disp_entities;
    if (!isset($disp_entities))
        $disp_entities = array();
    if (!isset($disp_entities['configuration']))
        $disp_entities['configuration'] = array();
    $saved_settings = array();
    foreach (USER_SETABLE_CONFIGS as $name => $scheme) {

        foreach (SETTINGS_TEMPLATE_TO_SAVE as $key => $is_required) {
            if (isset($scheme[$key]))
                $saved_settings[$name][$key] = $scheme[$key];
            elseif ($is_required) {
                parse_error("**BUG ALERT: ** Required variable $key not found in $name, I REFUSE TO SAVE THIS!");
            }
        }

    }

    //echo count($disp_entities)." saved to dispent<br />";
    $disp_entities['configuration']['saved_settings'] = $saved_settings;
}
// $expected type is array or not
function config_pull_from_saved($scheme)
{
    global $disp_entities;
    $variable = $scheme['name'];
    $is_array = $scheme['is_array'];
    if (config_save_mode_default($variable))
        return false;
    if (!isset($disp_entities))
        return false;
    if (!isset($disp_entities['configuration']))
        return false;
    if (!isset($disp_entities['configuration']['saved_settings']))
        return false;
    if (!isset($disp_entities['configuration']['saved_settings'][$variable]))
        return false;
    return $disp_entities['configuration']['saved_settings'][$variable];
}


function config_save_mode_default($variable = "all")
{
    $default_pv_name = CONFIG_POST_PREFIX . "default";
    $def_var = pv_or_blank($default_pv_name);
    if (!$def_var)
        return false;
    if ($def_var == "all")
        return true;
    if ($def_var == $variable)
        return true;
    return 0;
}

function config_save_mode_reset($variable = "all")
{
    $reset_pv_name = CONFIG_POST_PREFIX . "reset";
    if ($def_var = config_save_mode_default($variable))
        return $def_var;
    $res_var = pv_or_blank($reset_pv_name);
    if (!$res_var)
        return $def_var;
    if ($res_var == "all")
        return true;
    if ($res_var == $variable)
        return true;
    return 0;
}
function config_save_mode_pull($variable)
{
    $submit_pv_name = CONFIG_POST_PREFIX . "save";
    if ($res_var = config_save_mode_reset($variable))
        return false;
    if ($res_var === 0)
        return true;
    if (!($save_mode = pv_or_blank($submit_pv_name)))
        return false;
    if ($save_mode == "all")
        return true;
    if ($save_mode == $variable)
        return true;
    return false;
}
function config_save_mode_append($scheme)
{
    $submit_pv_name = CONFIG_POST_PREFIX . "append";
    if (!pv_or_blank($submit_pv_name))
        return false;

    $search_pv = CONFIG_POST_PREFIX . $scheme['name'];

    if (!pv_match($search_pv, false) || !$scheme['is_array'])
        // can only append arrays

        return false;

    return true;


}
function config_pull_from_post($scheme)
{

    if (!($append = config_save_mode_append($scheme)) && !config_save_mode_pull($scheme['name'])) {
        return false;
    }
    $ret_value = "";

    foreach (SETTINGS_TEMPLATE as $scheme_key => $make_var_name) {
        if (!isset($scheme[$scheme_key]))
            die("pull save from post fail: Scheme key '$scheme_key' not set in scheme $variable " .
                var_export($scheme, true));
        $$make_var_name = $scheme[$scheme_key];
    }
    if ($generated_value)
        return false;
    $raw_value = pv_or_else(CONFIG_POST_PREFIX . $variable, array());
    if ($is_array) {
        $ret_value = array();
        if ($append) {
            if (!($ret_value = $scheme['saved_value']))
                $ret_value = $scheme['default'];
        }

        $ignore_list = pv_or_else(CONFIG_POST_PREFIX . $variable . "_edit", array());
        $ignore_list = array_flip($ignore_list);

        config_add_post_array_value($raw_value, $variable, $key_wildcards);
        foreach ($raw_value as $raw_text) {
            if (isset($ignore_list[$raw_text]))
                continue;

            $ex = explode("@", $raw_text, 2);
            if (!isset($ex[1])) {
                $new_value = $ex[0];
                $new_key = "";
            } else
                list($new_key, $new_value) = $ex;

            $new_key = trim($new_key);
            if (!($new_value = trim($new_value))) {
                if ($new_key) {
                    parse_error("Cannot set '$key_label' -> '$new_key' in <b>$help</b>, <b>'$value_label'</b> is required and the one you set was invalid.");
                    return false;
                }
                continue;
            }
            if (!$key_label)
                $new_key = "";

            if (!$new_key) {
                $ret_value[] = $new_value;
            } else {
                if (isset($ret_value[$new_key])) {
                    parse_error("Cannot set '$new_value' -> '$value_label' in <b>$help</b>, it has duplicate $key_label <b>'$new_key'</b>");
                    return false;
                }
                $ret_value[$new_key] = $new_value;
            }
        }

    } else
        $ret_value = $raw_value;
    return $ret_value;
}
function config_add_post_array_value(&$output, $variable, $key_wildcards)
{
    $new_value = pv_or_blank(CONFIG_POST_PREFIX . $variable . "_value");
    $new_key = pv_or_blank(CONFIG_POST_PREFIX . $variable . "_key");
    $is_array = (is_array($new_value) ? 3 : 0) + (is_array($new_key) ? 2 : 0);
    $has_key = false;

    switch ($is_array) {
        case 0:
            append_to_post_value($new_value, $variable);
            $output[] = config_fix_key_from_post($new_key, $variable, $key_wildcards) . "@$new_value";
            return true;
        case 2:
            parse_error("new post value for $variable invalid, Key is array, value is not: key/array:");
            break;
        case 5:
            $has_key = true;
            if (count($new_key) && count($new_key) != count($new_value)) {
                parse_error("new post value for $variable invalid, array/key count mismatch");
                break;
            }
        case 3:
            foreach ($new_value as $i => $nv) {
                append_to_post_value($nv, $variable);
                if ($has_key) {
                    $output[] = config_fix_key_from_post($new_key[$i], $variable, $key_wildcards) .
                        "@$nv";
                } else
                    $output[] = $nv;
            }
            return true;
        default:
            die("okay what the fuck add post array value function");
    }
    parse_error($new_key);
    parse_error($new_value);
    return false;
}
function append_to_post_value(&$new_value, $variable, $delimeter = "[AND]")
{

    $appends = pv_or_blank(CONFIG_POST_PREFIX . $variable . "_append_to_value");
    if (!$appends || !is_array($appends))
        return $new_value;
    foreach ($appends as $a)
        $new_value .= "$delimeter$a";

    return $new_value;

}

function config_fix_key_from_post(&$item_key, $variable, $key_wildcards)
{
    if (!$key_wildcards)
        return $item_key;


    foreach ($key_wildcards as $wildcard)
        if ($item_key && $item_key == $wildcard)
            return append_random_crap_to_config_key($item_key);
    return $item_key;
}
function append_random_crap_to_config_key(&$item_key)
{
    $item_key = "$item_key/" . rand(0, 9999999);
    return $item_key;
}
function config_format_key($item_key, $variable = false)
{

    $type_to_var = array(
        "dict" => "dict_integrations",
        "setting" => "settings_integrations",
        "list" => "list_integrations");

    if (isset($type_to_var[$variable]))
        $variable = $type_to_var[$variable];

    $translated = translate_config_key($item_key, $variable, true);
    if ($variable == "list_name_scheme")
        $translated = format_list_scheme_key($translated);

    return $translated ? $translated : "<i> -- Default -- </i>";
}
function translate_config_key(&$item_key, $variable = false, $return_kw_label = false)
{
    if (is_numeric($item_key) || !$item_key)
        return ($item_key = "");
    if (!$variable)
        return $item_key;

    $type_to_var = array(
        "dict" => "dict_integrations",
        "setting" => "settings_integrations",
        "list" => "list_integrations");

    if (isset($type_to_var[$variable]))
        $variable = $type_to_var[$variable];

    $key_wildcards = get_config_scheme($variable, 'key_wildcards');
    if ($key_wildcards && $item_key) {
        $temp_key = $item_key;
        if (strpos($temp_key, "/") !== false)
            list($temp_key, $random_garbage) = explode("/", $temp_key, 2);
        foreach ($key_wildcards as $kw_label => $kw) {
            if ($temp_key == $kw) {
                if ($return_kw_label)
                    return "<i> -- $kw_label -- </i>";
                $item_key = "";
                return $kw;
            }
        }
    }
    return $item_key;
}

function config_format_value($item_value, $variable = false)
{
    if (!$variable)
        return $item_value;
    $type_to_var = array(
        "dict" => "dict_integrations",
        "setting" => "settings_integrations",
        "list" => "list_integrations");

    if (isset($type_to_var[$variable]))
        $variable = $type_to_var[$variable];

    $translated = $item_value;

    if ($variable == "list_name_scheme")
        $translated = format_list_scheme_value($translated);

    return $translated;
}
function format_list_scheme_key($item_key)
{
    $return = "<b>" . $item_key . "</b>";
    if (strpos($item_key, ":")) {
        list($field, $value) = explode(":", $item_key, 2);
        $explanation = "WHERE $field = $value";
        $return .= "<br />" . string_to_pre($explanation);
    }
    return $return;
}
function format_list_scheme_value($item_value)
{
    $return = "<b>" . $item_value . "</b>";
    $explanation = "SET LIST ENTITY NAME TO ";
    if (strpos($item_value, "[and]"))
        $values_array = explode("[and]", $item_value);
    else
        $values_array = array($item_value);

    $explanation .= "[" . implode("]" . LIST_NAME_CONCAT . "[", $values_array) . "]";

    $return .= "<br />" . string_to_pre($explanation);
    return $return;
}
// MUST BE RUN AFTER BUILD CONSTANTS, NOT BEFORE OR DURING
function get_config_scheme($variable, $scheme_key = false)
{
    if (!defined('USER_SETABLE_CONFIGS'))
        die("cannot get setting $variable because USER_SETABLE_CONFIGS not set yet");
    $usc = USER_SETABLE_CONFIGS;
    if (!isset($usc[$variable]))
        die("cannot get setting $variable because $variable is not in USER_SETABLE_CONFIGS");
    if (!$scheme_key)
        return $usc[$variable];
    $real_keys = SETTINGS_TEMPLATE_WITH_VALUE_FLIP;
    if (!isset($real_keys[$scheme_key]))
        die("cannot get setting $variable because key $scheme_key is a valid key");
    $real_key = $real_keys[$scheme_key];
    if (!isset($usc[$variable][$real_key]))
        die("cannot get setting $variable because key '$scheme_key' ($real_key) is not set");
    return $usc[$variable][$real_key];
}
function raw_lines_from_string($string)
{
    $ptext_nl2br = str_replace(array(
        "\r\n",
        "\n",
        "\r"), "<br />", $string);

    $raw_lines = explode('<br />', $ptext_nl2br);

    return $raw_lines;
}

?>