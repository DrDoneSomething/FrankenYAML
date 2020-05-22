<?php
defined("IN_YAML_HELPER") || die("not in helper");
/**
 * Almost all of these functions are used WITHIN the parse_code loop
 * they make references to that loop.

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/
$integrations_total = 0;
$entities_total = 0;
$nested_things_cache = array();
$nest_is_this_it = "";
$line_disabled = false;
$missing_files = array();
$ask_for_missing = array();
$inline_entities = array();
$ask_for_missing_list_scheme = array();
function ask_for_missing()
{
    global $ask_for_missing;
    if ($return = ask_for_missing_list_scheme())
        return $return;
    if (!$ask_for_missing)
        return false;

    if ($ask_for_missing)
        parse_error($ask_for_missing, true);

    require_once ("frankenyaml_config_page_functions.php");

    echo_saved_add_missing($ask_for_missing);

    return true;
}
function ask_for_missing_list_scheme()
{
    global $ask_for_missing_list_scheme;
    if (!$ask_for_missing_list_scheme)
        return false;
    $unprocessed_missing_list_schemes = array();
    foreach ($ask_for_missing_list_scheme as $key => $entity) {
        if ($entity['list_scheme_found'])
            continue;
        $entity['missing_list_key'] = $key;
        $unprocessed_missing_list_schemes[$key] = $entity;
    }
    if (!$unprocessed_missing_list_schemes)
        return false;

    if ($unprocessed_missing_list_schemes)
        parse_error($unprocessed_missing_list_schemes, true);

    require_once ("frankenyaml_config_page_functions.php");

    echo_saved_add_missing_list_scheme($unprocessed_missing_list_schemes);

    return true;
}
function dump_errors()
{
    global $parse_errors, $parse_errors_count;
    if ($return = error_state()) {
        echo "<b>PARSE ERRORS ($parse_errors_count):</b><br />";
        echo '<textarea rows="50" cols="50" name="text">' . implode("\n", $parse_errors) .
            '</textarea><hr />';

    } elseif ($warnings = parse_error()) {
        popup_msg_on_load($warnings);
    }
    return $return;
}
function rem_indent(&$str, $size = 2)
{
    $str = substr($str, $size);
    return $str;
}
function commit_integration()
{
    global $cur_integration, $cur_entity, $disp_entities, $last_comment, $integrations_total;
    if (!$cur_integration)
        return parse_error("no current entity to commit");
    $integrations_total++;

    if ($cur_entity)
        commit_entity();
    if (!$cur_integration['name'])
        parse_error("name not set");
    $name = $cur_integration['name'];
    //addcom($cur_integration);

    if (!isset($cur_integration['special_line']))
        $cur_integration['special_line'] = false;
    if (isset($cur_integration['subs']) && $cur_integration['subs'])
        ksort($cur_integration['subs']);

    if (isset($disp_entities[$name])) {
        if ($disp_entities[$name] !== $cur_integration) {
            parse_error("Note: replaced existing $name with incoming.", true, false);
        }
        unset($disp_entities[$name]);
    }
    add_recommended_nest();
    $disp_entities[$name] = $cur_integration;

    $cur_integration = array();
    //parse_error("new ent\n".var_export($disp_entities[$name],true));
    clear_nest();
}

function commit_disp()
{
    global $cur_integration, $missing_files, $file_type, $disp_entities, $ask_for_missing,
        $remove_from_missing;
    if ($cur_integration)
        commit_integration();

    build_integration("configuration");

    if ($remove_from_missing && !error_state())
        remove_from_missing($file_type);

    $cur_integration['missing_files'] = $missing_files;
    $cur_integration['ask_for_missing'] = $ask_for_missing;
    commit_integration();

    add_recommended_integrations();
    save_configuration();
    ksort($disp_entities);
    //packages: !include_dir_named integrations
}
function add_recommended_integrations()
{
    if (!ADD_RECOMMENDED_INTEGRATIONS)
        return;
    // probably should do this last...
    global $disp_entities;
    $added_recommended_nests = array();
    foreach (RECOMMENDED_INTEGRATIONS as $int_name) {
        if (strpos($int_name, "array_") === 0)
            continue;
        if (isset($disp_entities[$int_name]))
            continue;
        // We do not add empty nest integrations without disabling them
        $is_nest = (strpos($int_name, "/") !== false);
        if ($is_nest)
            $added_recommended_nests[] = $int_name;
        build_integration($int_name);
        commit_integration();

    }
    disable_unused_nests($added_recommended_nests);

}
function disable_unused_nests($list)
{
    if (!$list)
        return;
    global $disp_entities;
    foreach ($list as $nest_name) {
        if (strpos($nest_name, "/") === false)
            continue;
        if (!isset($disp_entities[$nest_name])) {
            parse_error("Attempted to disable $nest_name, could not be found (probably a bug)", true);
            continue;
        }
        list($int_name, $nest_garbage) = explode("/", $nest_name, 2);
        while (isset($disp_entities[$int_name])) {
            if ($disp_entities[$int_name]['disabled'])
                break;
            if (!isset($disp_entities[$int_name]['nest_data'][$nest_name]))
                break;
            if (!$disp_entities[$int_name]['nest_data'][$nest_name]['matched'])
                break;

            continue 2;
        }
        //parse_error("disabling $nest_name", true);
        disable_item_recursive($disp_entities[$nest_name], "DISABLING BECAUSE PARENT $int_name DOES NOT EXIST OR IS DISABLED");
    }
}
function disable_item_recursive(&$item, $add_comment = false)
{
    $item['disabled'] = true;
    if ($add_comment) {
        if (substr($add_comment, 0, 3) !== "#*#")
            $add_comment = "#*# $add_comment";
        $item['contents'] = $add_comment . "\n" . $item['contents'];
    }
    if (isset($item['subs']) && $item['subs'])
        foreach ($item['subs'] as $sub_item)
            disable_item_recursive($sub_item, $add_comment);
}
function integ_val($key, $cur_integration = false)
{
    if (!$cur_integration)
        global $cur_integration;
    if (!isset($cur_integration) || !$cur_integration)
        return false;
    if (!isset($cur_integration[$key]))
        return false;
    return $cur_integration[$key];
}


function commit_entity()
{
    global $cur_integration, $cur_entity, $disp_entities, $last_comment, $entities_total;
    if (!$cur_entity)
        return parse_error("no current entity to commit");
    if (!$cur_integration)
        return parse_error("no cur_integration to place into... ");
    $entities_total++;

    //addcom($cur_entity);
    if (!isset($cur_integration['subs']))
        $cur_integration['subs'] = array();
    if (!isset($cur_entity['special_line']))
        $cur_entity['special_line'] = false;


    if (!($name = name_that_entity())) {
        parse_error("entity has no name, probably need to add a naming scheme to config");
        $cur_entity = array();
        return;

    }
    $cur_entity['entity_path'] = $cur_integration['entities_path'] . "/$name.yaml";

    if (isset($cur_integration['subs'][$name])) {
        parse_error("Entity already exists $name");
        $cur_entity = array();
        return;

    }
    $cur_entity['contents'] = entity_text($cur_entity);

    add_recommended_nest();
    $cur_integration['subs'][$name] = $cur_entity;
    $cur_entity = array();

}

function ent_val($key, $cur_entity = false)
{

    if (!$cur_entity)
        global $cur_entity;

    if (!isset($cur_entity) || !$cur_entity)
        return false;
    if (!isset($cur_entity[$key]))
        return false;
    return $cur_entity[$key];
}

function ent_field_val($key, $cur_entity = false)
{

    if (!$cur_entity)
        global $cur_entity;

    if (!($fields = ent_val('fields', $cur_entity)))
        return false;
    if (!isset($fields[$key]))
        return false;
    return $fields[$key];
}

function match_type($value, $pattern)
{

    if ($pattern == $value)
        return true;
    $pattern = preg_quote($pattern, '#');

    // Asterisks are translated into zero-or-more regular expression wildcards
    // to make it convenient to check if the strings starts with the given
    // pattern such as "library/*", making any string check convenient.
    $pattern = str_replace('\*', '.*', $pattern) . '\z';

    return (bool)preg_match('#^' . $pattern . '#', $value);
}
function build_integration($name = false, $disable_integration = false)
{
    global $disp_entities, $cur_integration;
    if ($name) {
        unset($cur_integration);
        global $cur_integration;
        if (isset($disp_entities[$name])) {
            $cur_integration = $disp_entities[$name];
            $disp_entities[$name] = &$cur_integration;

            return $cur_integration;
        }
        $cur_integration = array();
        $cur_integration['name'] = $name;

    } else {
        if (!isset($cur_integration['name'])) {
            parse_error("could not create entity, no name found: ");
        }
        $name = $cur_integration['name'];

    }
    get_nested_things($cur_integration);
    // sets entities dir as well
    integration_add_details($cur_integration);
    //input_datetime: !include_dir_named ../entities/input_datetime

    if ($name == 'configuration')
        $cur_integration['integration_path'] = "$name.yaml";
    else
        $cur_integration['integration_path'] = INTEGRATIONS_LOCATION . "/$name.yaml";


    if (!$cur_integration['translated_path']) {
        if ($cur_integration['generated'])
            $cur_integration['entities_path'] = ENTITIES_LOCATION . "/" . clean_string($cur_integration['name']);
        else
            $cur_integration['entities_path'] = ENTITIES_LOCATION . "/{$cur_integration['name']}s";
    } else
        $cur_integration['entities_path'] = $cur_integration['translated_path'];


    $cur_integration['entities_relative_path'] = getRelativePath($cur_integration['integration_path'],
        $cur_integration['entities_path']);

    $cur_integration['subs'] = array();

    integration_add_text($cur_integration);
    if ($disable_integration)
        $cur_integration['disabled'] = true;
    return $cur_integration;
}
function hypothetical_integration_type($integration_name, $return_setting =
    'type')
{
    $hypothetical_integration = array('name' => $integration_name);
    return integration_type($hypothetical_integration, $return_setting);
}
function integration_type(&$cur_integration, $return_setting = 'type')
{
    for ($i = 0; $i < 2; $i++) {
        switch ($return_setting) {
            case 'type':
            case 'translated_path';
            case 'alt_path':
            case 'disabled':
            case 'entities_inline';
                if (isset($cur_integration[$return_setting]))
                    return $cur_integration[$return_setting];
                break;
            default:
                die("Invalid request from this function integration_type: $return_setting. This function does not set this value.");
        }
        if ($i)
            break;


        $search_arrays = array(
            "setting" => SETTINGS_INTEGRATIONS_FLIPPED,
            "dict" => DICT_INTEGRATIONS_FLIPPED,
            "list" => LIST_INTEGRATIONS_FLIPPED);
        $disabled_arrays = array(
            "setting" => SETTINGS_INTEGRATIONS_DISABLED,
            "dict" => DICT_INTEGRATIONS_DISABLED,
            "list" => LIST_INTEGRATIONS_DISABLED);

        $type = integ_val("type", $cur_integration);

        if (!$type)
            foreach ($search_arrays as $this_type => $arr) {
                if (isset($arr[$cur_integration['name']])) {
                    $alt_path = $arr[$cur_integration['name']];
                    $type = $this_type;
                    break;
                }
            }

        if (!$type)
            foreach ($disabled_arrays as $this_type => $arr) {
                if (isset($arr[$cur_integration['name']])) {
                    $alt_path = $arr[$cur_integration['name']];
                    $type = $this_type;
                    $cur_integration['disabled'] = true;
                    break;
                }
            }


        if (!$type)
            foreach (SETTINGS_INTEGRATIONS as $alt_path => $setting) {
                if (match_type($cur_integration['name'], $setting)) {
                    $type = 'setting';
                    break;
                }
            }

        if (!$type)
            foreach (DICT_INTEGRATIONS as $alt_path => $dict) {
                if (match_type($cur_integration['name'], $dict)) {
                    $type = 'dict';
                    break;
                }
            }


        if (!$type)
            foreach (LIST_INTEGRATIONS as $alt_path => $list) {
                if (match_type($cur_integration['name'], $list)) {
                    $type = 'list';
                    break;
                }
            }

        if (!$type) {
            $type = 'setting';
            $alt_path = 0;
            parse_error("Warning: Cannot find type for integration {$cur_integration['name']}, selecting $type so it will parse, but this is not cool. " .
                " You must select one.", true);
            global $ask_for_missing;
            if (!isset($ask_for_missing[$cur_integration['name']])) {

                $ask_for_missing[$cur_integration['name']]['type'] = 0;
                $ask_for_missing[$cur_integration['name']]['ask_location'] = true;
            }
        }
        $cur_integration['type'] = $type;

        $cur_integration['translated_path'] = translate_config_key($alt_path, $type);
        $cur_integration['alt_path'] = $alt_path;
        if (!isset($cur_integration['disabled']))
            $cur_integration['disabled'] = false;

        $cur_integration['entities_inline'] = ($cur_integration['translated_path'] ==
            SAVE_IN_INTEGRATION_PATH) ? true : false;
        $cur_integration['required_file'] = (substr($cur_integration['translated_path'],
            -5) == ".yaml");
    }
    parse_error("Requested value $return_setting could not be returned in integration_type... Seems like a bug");

    return false;
}
// sets enties location as well, needs name
// sets entities path
function integration_add_details(&$cur_integration)
{
    if (!$cur_integration || !$cur_integration['name'])
        die("Cannot get integration_type, passed variable has no name set");


    $is_configuration = ('configuration' == integ_val('name', $cur_integration));
    $cur_integration['generated'] = strpos($cur_integration['name'], "/") ? true : false;
    $alt_path = 0;

    if (isset($cur_integration['disabled']) || ($cur_integration['disabled'] = false)) {
        if ($cur_integration['disabled'] && $is_configuration) {
            parse_error("Configuration has been set to Disabled. I am not sure how that happened. Let me fix that for you.", true);
            $cur_integration['disabled'] = false;
        }
    }


    get_nested_things($cur_integration);
    $type = integration_type($cur_integration);
    $alt_path = integration_type($cur_integration, 'alt_path');
    $translated_path = integration_type($cur_integration, 'translated_path');


    if (!$alt_path) {
        if ($cur_integration['generated'])
            $cur_integration['entities_path'] = ENTITIES_LOCATION . "/" . clean_string($cur_integration['name']);
        else
            $cur_integration['entities_path'] = ENTITIES_LOCATION . "/{$cur_integration['name']}s";
    } else
        $cur_integration['entities_path'] = $alt_path;


    return $type;

}

// integration_type MUST BE RUN FIRST!
function integration_add_text(&$cur_integration)
{
    if (!isset($cur_integration['contents']))
        $cur_integration['contents'] = "";
    $text = $pre_comments_text = array();
    $new_file = false;

    $text_block = $type = integration_type($cur_integration);
    //$text[] = "# " . var_export($cur_integration['nest_array'], true);
    if (integ_val('generated', $cur_integration))
        $text_block = 'generated';
    else
        $text[] = "#*# LOCATION: {$cur_integration['integration_path']}";
    //***** NOTE: PRE_COMMENTS_TEXT HAVE TO BE ADDED IN REVERSE ORDER OF HOW YOU WANT THEM TO APPEAR *****
    $add_to_inline_search_string = "";
    if (substr($cur_integration['entities_relative_path'], -5) == ".yaml")
        $text_block = "yaml_file";
    switch ($text_block) {
        case 'generated':
            $type = "Generated, parse as $type";
            $pre_comments_text[] =
                "#*# THIS IS A FAKE INTEGRATION, IT SHOULD NOT BE PLACED ANYWHERE";
            $text[] = "#*# ENTITIES LOCATION:  {$cur_integration['entities_path']}";
            //$text[] = "# ORIGINATOR :  integration_path";
            //$text[] = var_export($cur_integration,true);
            break;
        case 'yaml_file':
            $text[] = "#*# HELP: https://www.home-assistant.io/integrations/{$cur_integration['name']}";
            $text[] = $add_to_inline_search_string = "{$cur_integration['name']}: !include {$cur_integration['entities_relative_path']}";
            $new_file = $cur_integration['entities_path'];
            $cur_integration['entities_inline'] = $cur_integration['entities_relative_path'];
            break;

        case 'list':
            $text[] = "#*# HELP: https://www.home-assistant.io/integrations/{$cur_integration['name']}";
            $text[] = $add_to_inline_search_string = "{$cur_integration['name']}: !include_dir_list {$cur_integration['entities_relative_path']}";
            break;
        case 'dict':
            $text[] = "#*# HELP: https://www.home-assistant.io/integrations/{$cur_integration['name']}";
            $text[] = $add_to_inline_search_string = "{$cur_integration['name']}: !include_dir_merge_named {$cur_integration['entities_relative_path']}";
            break;
        case 'setting':
            $text[] = "#*# HELP: https://www.home-assistant.io/integrations/{$cur_integration['name']}";
            $text[] = "{$cur_integration['name']}:";

            break;
        case 'configuration':
            $text[] = "homeassistant:";
            $text[] = "  packages: !include_dir_named " . INTEGRATIONS_LOCATION;
            break;
        default:
            return parse_error("integration text not supported for this type {$cur_integration['type']} ");

            break;
    }
    if ($cur_integration['entities_inline']) {
        global $inline_entities;
        add_to_inline($cur_integration['name'], $cur_integration['name'], $add_to_inline_search_string,
            $new_file);
        if (!$new_file) {
            $text[] = "#*# NOTICE: OPTION TO DUMP THE ENTITIES WITHIN THIS INTEGRATION HAS BEEN SELECTED. ";
            $text[] = "#*#   IF EVERTHING  PARSED CORRECTLY, THIS WILL HAVE NO SEPARATE ENTITY FILES.";
            $text[] = "#*#   IT WILL DUMP ALL ENTITIES DIRECTLY WITHIN INTEGRATION '{$inline_entities[$cur_integration['name']]['int_to']}'";
        }
    }

    $pre_comments_text[] = "#*# TYPE: $type";
    $pre_comments_text[] = "#*# INTEGRATION {$cur_integration['name']}";
    foreach ($text as $line)
        commit_line($cur_integration, $line);
    foreach ($pre_comments_text as $line)
        commit_line($cur_integration, $line, true);
}
function set_if_not_set(&$array, $key, $value)
{
    if (!isset($array[$key])) {
        $array[$key] = $value;
    }
}
function remove_from_missing($name)
{
    global $missing_files;
    if (isset($missing_files[$name])) {
        unset($missing_files[$name]);
    }
    if (isset($ask_for_missing[$name])) {
        unset($ask_for_missing[$name]);
    }

}
function add_to_missing_list_scheme($entity)
{
    global $ask_for_missing_list_scheme;
    $entity['list_scheme_found'] = false;
    $ask_for_missing_list_scheme[] = $entity;
}
function add_and_parse_include_path($suggested_name=false, $suggested_entity_type = false, $suggested_file_reference = "", $ask_location = false)
{
    global $missing_files, $raw_line, $cur_entity, $cur_integration, $ask_for_missing;
    if (strpos($raw_line, "!include") === false)
        return;
        
    $colon_ex = explode(":",$raw_line);
    if(count($colon_ex)!=2)
    {
        parse_error("Expected [integration name]: !include ..., got this crap;");
        return;
    }
    list($indented_name, $include_data) = $colon_ex;
    $found_name = trim($indented_name);
    if($suggested_name && strpos($suggested_name,"/"))
    {
        $sn_ex = explode("/",$suggested_name);
        $suggested_item_name = $sn_ex[count($sn_ex)-1];
        
        if($found_name != $suggested_item_name)
        {
            parse_error("Expected current item of $suggested_name to be $suggested_item_name, but it was $found_name.");
            return;
        }
    }
    else
        $suggested_item_name = $suggested_name;
    
    $search = array(
        '!include ' => 0,
        '!include_dir_list ' => 'list', // each file has a single list item
        '!include_dir_named ' => 'dict', // annoying pain in the dict - file name IS the base thingy
        '!include_dir_merge_list ' => 'list', // list merge with each item has a dash
        '!include_dir_merge_named ' => 'dict'); // can contain any amount of DICTS

    $path = "";
    $output = "";
    $found_type = "";
    //$found_type = "none";

    foreach ($search as $incstr => $found_type) {
        if (strpos($raw_line, $incstr)) {
            list($entity, $path) = explode($incstr, $raw_line);
            $path = trim($path);
            break;
        }
    }
    if (!$entity) {
        parse_error("Could not add $name to missing, include method is not recognized.. ");
        return;

    }
    
    if (!$path) {
        parse_error("Could not add $name to missing, no path found $output...\n\n");
        return;
    }
    // I TOOK THIS OUT, DOES NOT SEEM NECESSARY
    if (false && $entity_type != $found_type && $found_type && $entity_type) {
        parse_error("Could not add $name to missing, you had me look for a $entity_type, but it ended up being a $found_type. You might need to change your parse settings");
        $entity_type = 0;
    }

    while (!$entity_type) {
        if ($found_type) {
            $entity_type = $found_type;
            break;
        }

        if ($entity_type = hypothetical_integration_type($name))
            break;
        $entity_type = $found_type;

        //parse_error("Could not add $name to missing, could not determine type...");
        //return;
        break;
    }
    
}

// searches raw line for included files that we should ask for later
function add_to_missing($name, $entity_type = false, $file_reference = "", $ask_location = false)
{
    global $missing_files, $raw_line, $cur_entity, $cur_integration, $ask_for_missing;
    if (strpos($raw_line, "!include") === false)
        return;

    list($indented_name, $include_data) = explode(":", $raw_line);

    $search = array(
        '!include ' => 0,
        '!include_dir_list ' => 'list', // each file has a single list item
        '!include_dir_named ' => 'dict', // annoying pain in the dict - file name IS the base thingy
        '!include_dir_merge_list ' => 'list', // list merge with each item has a dash
        '!include_dir_merge_named ' => 'dict'); // can contain any amount of DICTS

    $path = "";
    $output = "";
    $found_type = "";
    //$found_type = "none";


    foreach ($search as $incstr => $found_type) {
        if (strpos($raw_line, $incstr)) {
            list($entity, $path) = explode($incstr, $raw_line);
            $path = trim($path);
            break;
        }
    }
    if (!$entity) {
        parse_error("Could not add $name to missing, include method is not recognized.. ");
        return;

    }
    if (!$path) {
        parse_error("Could not add $name to missing, no path found $output...\n\n");
        return;
    }
    // I TOOK THIS OUT, DOES NOT SEEM NECESSARY
    if (false && $entity_type != $found_type && $found_type && $entity_type) {
        parse_error("Could not add $name to missing, you had me look for a $entity_type, but it ended up being a $found_type. You might need to change your parse settings");
        $entity_type = 0;
    }

    while (!$entity_type) {
        if ($found_type) {
            $entity_type = $found_type;
            break;
        }

        if ($entity_type = hypothetical_integration_type($name))
            break;
        $entity_type = $found_type;

        //parse_error("Could not add $name to missing, could not determine type...");
        //return;
        break;
    }

    $missing_files[$name] = array(
        'type' => $entity_type,
        'name' => $name,
        'path' => $path,
        'found_type' => $found_type,
        'include_request' => trim($incstr),
        'file_reference' => $file_reference,
        'ask_location' => $ask_location);
    if (!$entity_type || $ask_location)
        $ask_for_missing[$name] = $missing_files[$name];

    return $entity_type;
}
function entity_text($cur_entity, $cur_integration = false)
{
    global $inline_entities;
    if (!$cur_integration) {
        global $cur_integration;
    }
    if (!$cur_integration) {
        return parse_error("no cur_entity found for subentity text ");
    }
    if (isset($inline_entities[$cur_integration['name']]))
        return $cur_entity['contents'];
    $text = array();
    $text[] = "#*# ENTITY";
    $text[] = "#*# TYPE: {$cur_integration['type']}";
    if (!$cur_integration['generated'])
        $text[] = "#*# INTEGRATION LOCATION: {$cur_integration['integration_path']}";
    $text[] = "#*# ENTITY LOCATION: {$cur_entity['entity_path']}";


    if (!isset($cur_entity['contents']) || !$cur_entity['contents'])
        $text[] = parse_error("entity has no contents IN {$cur_integration['name']} ");
    else
        $text[] = $cur_entity['contents'];

    return implode("\n", $text) . "\n\n";

}
function format_name(&$array)
{
    if (!$array)
        return false;
    if (!isset($array['name']))
        return false;
    if (!$array['name'])
        return false;

    $str = $array['name'];
    $array['name'] = clean_string($str);
    return $str;
    // THE FOLLOWING IS NOT USED
    $string = $array['name'];
    // Put any language specific filters here,
    // like, for example, turning the Swedish letter "å" into "a"

    // Remove any character that is not alphanumeric, white-space, or a hyphen
    $string = preg_replace('/[^a-z0-9\s\_]/i', '', $string);
    // Replace all spaces with hyphens
    $string = preg_replace('/\s/', '.', $string);
    // Replace multiple hyphens with a single hyphen
    $string = preg_replace('/\.\-+/', '.', $string);
    // Remove leading and trailing hyphens, and then lowercase the URL
    $string = strtolower(trim($string, '.'));
    $array['name'] = $string;
    return $string;
}
function clean_string($str)
{

    $str = preg_replace('/[^\w\s]+/', '_', $str);
    $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
    $str = strtolower($str);
    return $str;
}
// since everything's going in the integrations folder, if they've got an include already we need to change relative dir''
function change_include_path($raw_line)
{
    // KEEP THE DAMN SPACE AFTER THE INCLUDE!!
    $search = array(
        '!include ',
        '!include_dir_list ',
        '!include_dir_named ',
        '!include_dir_merge_list ',
        '!include_dir_merge_named ');
    foreach ($search as $incstr) {
        if (strpos($raw_line, $incstr)) {
            list($entity, $path) = explode($incstr, $raw_line);
            $path = trim($path);
            $path = getRelativePath(INTEGRATIONS_LOCATION . "/x.balls", $path);
            $raw_line = "$entity$incstr$path";
        }
    }
    return $raw_line;
}
function commit_line(&$array, $raw_line = false, $append_first = false)
{
    if (!$raw_line)
        global $raw_line;
    isset($array['contents']) || ($array['contents'] = "");
    if (substr($raw_line, 0, strlen(DISABLED_ITEM_PREFIX)) == DISABLED_ITEM_PREFIX) {
        $array['special_line'] = true;
    }
    addcom($array);
    if (!$append_first)
        $array['contents'] .= "$raw_line\n";
    else
        $array['contents'] = "$raw_line\n{$array['contents']}";

}
function addcom(&$array)
{
    global $last_comment;
    if (!RETAIN_YAML_COMMENTS)
        return;

    isset($array['contents']) || ($array['contents'] = "");
    $array['contents'] .= $last_comment;
    $last_comment = "";

}
function add_to_list_entity_fields()
{
    global $raw_line, $cur_entity, $cur_integration;

    if (!strpos("$raw_line", ":"))
        return false;

    if (!isset($cur_entity['fields']) || !$cur_entity['fields'])
        $cur_entity['fields'] = $cur_entity['fields_formatted'] = array('integration_name' =>
                $cur_integration['name'], 'integration_type' => $cur_integration['type']);


    if (substr($raw_line, 0, 2) != "  ") {
        list($item, $value) = explode(":", $raw_line, 2);

        $item = trim($item);
        $value = trim(str_replace(array("'", '"'), "", $value));
        if (!$item || !$value)
            return false;

        switch ($item) {
            case 'name':
            case 'item':
            case 'platform':
            case 'mac':
            case 'host':
            default:
                $cur_entity['fields'][$item] = $value;
                $cur_entity['fields_formatted'][$item] = clean_string($value);
                break;
        }

    }

}
function name_that_entity($cur_entity = false, $cur_integration = false)
{

    if (!$cur_integration)
        global $cur_integration;
    if (!$cur_integration)
        return parse_error("cannot name entity, integration not set yet ");

    if (!$cur_entity)
        global $cur_entity;
    if (!$cur_entity)
        return parse_error("cannot name entity, not set yet ");
    // already named
    if ($name = ent_val('name', $cur_entity))
        return $name;
    $integration_name = integ_val('name', $cur_integration);

    $cur_entity['name'] = "";
    if ($integ_type = integ_val('type', $cur_integration) !== 'list') {
        parse_error("could not name entity, integration '$integration_name' has subs which are '$integ_type'. As such, it should have a name defined already");

        return ent_val('name', $cur_entity);
    }

    $name_fail_state = false;
    $search_found = false;

    foreach (LIST_NAME_SCHEME as $search => $set_to) {
        list($search_field, $search_value) = explode(":", $search);
        $search_field = trim($search_field);
        $search_value = trim($search_value);
        $set_to_array = array();
        if ($search_value == ent_field_val($search_field, $cur_entity)) {
            $search_found = true;
            $set_to = str_replace("[and]", "[AND]", $set_to);
            if (strpos($set_to, '[AND]')) {
                $set_to_array = explode("[AND]", $set_to);
            } else
                $set_to_array[] = $set_to;
            $concat_this = array();
            foreach ($set_to_array as $namefield) {
                $tmpname = ent_field_val($namefield, $cur_entity);
                if ($tmpname === false) {
                    parse_error("cannot name entity, problem with the name, field not found: $namefield for item with $search_field = $search_value");
                    $name_fail_state = true;
                    continue;

                }
                $concat_this[] = $tmpname;

            }
            $cur_entity['name'] = implode(LIST_NAME_CONCAT, $concat_this);


            if (!format_name($cur_entity) || $name_fail_state) {

                $name_fail_state = true;
                parse_error("cannot name entity, problem with the name, field(s) probably not found: $set_to");
            }
            break;

        }

    }
    if (!$search_found) {
        parse_error("cannot name entity, problem with the name, field(s) probably not found, may need to add platform to list_name_scheme");
        add_to_missing_list_scheme($cur_entity);

    }


    //exit;
    return ent_val('name', $cur_entity);

}
function getRelativePath($from, $to)
{
    // some compatibility fixes for Windows paths
    $from = gRP_is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to = gRP_is_dir($to) ? rtrim($to, '\/') . '/' : $to;
    $from = str_replace('\\', '/', $from);
    $to = str_replace('\\', '/', $to);

    $from = explode('/', $from);
    $to = explode('/', $to);
    $relPath = $to;

    foreach ($from as $depth => $dir) {
        // find first non-matching dir
        if ($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if ($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
}
function gRP_is_dir($str)
{
    $ar = explode('/', $str);
    $last = $ar[count($ar) - 1];
    return strpos($last, ".") === false;
}
function get_nested_things(&$cur_integration)
{
    if (isset($cur_integration['nest_data']))
        return;
    get_nested_cache();
    global $nested_things_cache;

    $name = $cur_integration['name'];
    $cur_integration['nest_array'] = array();
    $cur_integration['nest_data'] = array();
    if (isset($nested_things_cache[$name])) {
        $cur_integration['nest_array'] = $nested_things_cache[$name];
        $cur_integration['nest_data'] = $nested_things_cache['NTC_CONFIG'][$name];
    }
}
function get_nested_cache()
{
    global $nested_things_cache;
    if ($nested_things_cache)
        return;
    clear_nest();
    if (!isset($nested_things_cache['NTC_CONFIG']))
        $nested_things_cache['NTC_CONFIG'] = array();
    append_nested_things_cache('list', NESTED_LIST_FLIPPED);
    append_nested_things_cache('list', NESTED_LIST_DISABLED, true);
    append_nested_things_cache('dict', NESTED_DICT_FLIPPED);
    append_nested_things_cache('dict', NESTED_DICT_DISABLED, true);
    append_nested_things_cache('configuration', NESTED_CONFIGURATION_FLIPPED);
}
function append_nested_things_cache($entity_type, $input, $disabled = false)
{
    global $nested_things_cache, $entities_location;
    $disabled_text = $disabled ? "disabled" : "enabled";
    foreach ($input as $raw_text => $key) {
        if (isset($nested_things_cache['NTC_CONFIG']['ALL_INTEGRATIONS'][$raw_text])) {
            die("Config error: duplicate $raw_text  $disabled_text for nested things <pre>" .
                var_export($nested_things_cache['NTC_CONFIG']['ALL_INTEGRATIONS'][$raw_text], true));
        }
        if (!strpos($raw_text, "/"))
            die("Config error: nested things must have at least one / $raw_text ");

        $config_variable = "nested_$entity_type";

        $translated = translate_config_key($key, $config_variable);
        $entities_inline = ($translated == SAVE_IN_INTEGRATION_PATH);

        $ent_loc = $key;


        $cur = &$nested_things_cache;
        $items = explode("/", $raw_text);
        $integration_name = "";
        $partial_match = array();
        for ($i = 0; $i < (count($items) - 1); $i++) {
            if (!isset($cur[$items[$i]]))
                $cur[$items[$i]] = array();
            $partial_match[] = $items[$i];
            $cur = &$cur[$items[$i]];
            if (!$i)
                $integration_name = $items[$i];

        }
        $cur = $items[count($items) - 1];

        if (count($items) == 2) {

            $pmatch = array(
                'raw_line' => false,
                'integration' => $integration_name,
                'entity' => false,
                'total_indent' => 2);
        } else
            $pmatch = false;

        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['last_item'] =
            $cur;
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['entities_inline'] =
            $disabled;
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['disabled'] = $disabled;
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['type'] = $entity_type;
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['text'] = $raw_text;
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['matched'] = false; // set by match loop
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['partial_matched'] =
            $pmatch; // set by match loop


        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['recommended'] =
            in_array($raw_text, RECOMMENDED_INTEGRATIONS);

        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['depth'] =
            count($items);
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['partial'] =
            implode("/", $partial_match);
        $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text]['entities_location'] =
            $ent_loc;

        $nested_things_cache['NTC_CONFIG']['ALL_INTEGRATIONS'][$raw_text] = $nested_things_cache['NTC_CONFIG'][$integration_name][$raw_text];
    }
}
// CALL AT COMMITS!!
// CALL BEFORE SOMETHING ALTERS CONTENTS TEXT
function add_recommended_nest()
{
    if (!ADD_RECOMMENDED_INTEGRATIONS)
        return;
    global $cur_entity, $cur_integration;
    foreach ($cur_integration['nest_data'] as $raw_text => $data) {
        if (!$data['matched'] && $data['partial_matched'] && $data['recommended']) {
            insert_missing_nest($data);
        }
    }


}
function insert_missing_nest($match)
{
    global $cur_entity, $cur_integration;
    //$pmatch = array('raw_line'=>$raw_line,'integration'=>$cur_integration['name'],'entity'=>true,'total_indent'=>$total_indent);
    $raw_text = $match['text'];
    $last_item = $match['last_item'];

    $pmatch = $match['partial_matched'];
    $raw_search_line = $pmatch['raw_line'];
    $total_indent = $pmatch['total_indent'];
    $is_entity = $pmatch['entity'];

    $cur_integration['nest_data'][$raw_text]['matched'] = true;
    $cur_integration['nest_data'][$raw_text]['partial_matched'] = false;


    $base_path = $dir_name = $relative_path = $include = $new_path = "";
    build_nest_line($match, $base_path, $dir_name, $relative_path, $include, $new_path);
    if ($cur_entity)
        $contents = &$cur_entity['contents'];
    else
        $contents = &$cur_integration['contents'];

    if ($raw_search_line === false)
        $position = 0;
    else
        $position = strpos($contents, $raw_search_line);

    if ($position === false) {
        parse_error("could not add missing nest, search line not found: $raw_search_line");
        return;
    }

    $insert_string = str_repeat(" ", $total_indent) .
        "#*# RECOMMENDED ENTITY/INTEGRATION ADDED: $raw_text\n";

    $insert_string .= str_repeat(" ", $total_indent) . "$last_item: $include $relative_path\n";
    if (!$position)
        $contents .= $insert_string;
    else
        $contents = substr_replace($contents, $insert_string, $position, 0);

}

function clear_nest()
{
    global $nest_is_this_it, $nest_completed;
    $nest_completed = array();
    $nest_is_this_it = "";
    $nesting_currently = array();
}
function check_nest()
{
    global $cur_entity, $cur_integration, $raw_line, $nest_is_this_it, $last_comment,
        $pm;
    $max_en = 20;
    get_nested_things($cur_integration);


    if (!$nest_is_this_it)
        $nest_is_this_it = $cur_integration['name'];


    if (substr($raw_line, 0, 2) != "  ") {
        //parse_error("check nest is confused -- should have an indent here. Maybe the yaml was flawed, maybe check nest called at wrong time");
    }
    $temp_line = $raw_line;

    if ($pm != 'configuration')
        $temp_line = "  " . $temp_line;

    $cur_path = explode("/", $nest_is_this_it);
    $cur_level = count($cur_path);
    $prev_nest_info = "Prev Level: $cur_level prev path: '$nest_is_this_it'";
    $total_indent = 0;
    for ($level_count = 1; $size = return_indent_size($temp_line); ) {
        $level_count++;
        $total_indent += $size;
        rem_indent($temp_line, $size);
    }

    if (!strpos($temp_line, ":"))
        $entity_name = "";
    else {
        $entity_name = str_replace(array(" ", "/"), array("", "_"), $temp_line);

        list($entity_name, $crap) = explode(":", $entity_name);

        if (strlen($entity_name) > $max_en)
            $entity_name = substr($entity_name, 0, $max_en);


    }

    while ($cur_level > $level_count) {
        unset($cur_path[$cur_level - 1]);
        $cur_level = count($cur_path);
    }
    if ($cur_level == $level_count) {
        // the last cur path should equal entity_name, otherwise swap
        if ($cur_path[$cur_level - 1] != $entity_name) {
            $cur_path[$cur_level - 1] = $entity_name;
        }
    }
    if ($cur_level < $level_count) {
        if ($level_count > $cur_level + 1) {

            //parse_error("check nest got a problem here: We missed a level..");
        }
        $cur_path[] = $entity_name;
        $cur_level = count($cur_path);
    }

    $nest_is_this_it = implode("/", $cur_path);
    $part_path = $cur_path;
    array_pop($part_path);
    $partial = implode("/", $part_path);

    if (strpos($raw_line, "!include") && !isset($cur_integration['nest_data'][$nest_is_this_it])) {
        $guessed_type = add_to_missing($nest_is_this_it, false, false, true);

        make_include_behave($guessed_type);

    }

    if (!$cur_integration['nest_array'])
        return false;


    foreach ($cur_integration['nest_data'] as $text => $data) {
        $matchme = array();
        for ($i = 0; $i <= substr_count($text, "/"); $i++) {
            if (!isset($cur_path[$i]))
                break;
            $matchme[] = $cur_path[$i];
        }
        $matchme = implode("/", $matchme);

        if ($text == $matchme) {

            $cur_integration['nest_data'][$text]['matched'] = true;

            return nesting_in_progress($total_indent, $data);
            //return true;
        }
        if (!$data['partial_matched'] && $partial == $data['partial']) {
            $pmatch = array(
                'raw_line' => $raw_line,
                'integration' => $cur_integration['name'],
                'entity' => ($cur_entity ? true : false),
                'total_indent' => $total_indent);

            $cur_integration['nest_data'][$text]['partial_matched'] = $pmatch;

            //return true;
        }

    }
    //echo "#NOT IT: $nest_is_this_it en: $entity_name\n";
    //echo "# NOT it TL: $temp_line \n";
    return false;

}
function add_to_inline($subs_from, $int_to, $search_line, $new_file = false)
{
    global $inline_entities;
    if (isset($inline_entities[$subs_from]))
        return;

    if (!$subs_from || !$int_to || !$search_line) {
        parse_error("cannot add to inline entities list, something missing: subs_from: '$subs_from' int_to: '$int_to', search_line: '$search_line'");
        return;
    }

    while (substr($tsl = trim($search_line), -5) == ".yaml") {
        if ($new_file)
            break;
        if (strpos($search_line, "!include") === false) {
            parse_error("Expected !include [yaml file] in search line for $subs_from to add inline, found $search_line");
            return;
        }
        list($int, $file) = explode("!include", $search_line, 2);
        if (substr($file, 0, 1) != " ") {
            parse_error("Expected !include [yaml file] in search line for $subs_from to add inline, found $search_line");
            return;
        }
        $new_file = trim($file);
        break;
    }


    $new_addition = array(
        "subs_from" => $subs_from,
        "int_to" => $int_to,
        "search_line" => $search_line,
        "new_file" => $new_file);
    $inline_entities[$subs_from] = $new_addition;
    // LAST IN FIRST OUT, BITCH
}
function execute_inlines()
{
    global $inline_entities, $disp_entities;
    $possible_types = array("dict" => "dict", "list" => "list");
    foreach ($inline_entities as $entry) {
        foreach ($entry as $key => $val)
            $$key = $val;
        $subs_from_type = $disp_entities[$subs_from]['type'];
        $int_to_type = $disp_entities[$int_to]['type'];

        if (!isset($possible_types[$subs_from_type])) {
            parse_error("$subs_from ($subs_from_type) is not a dict or list");
            continue;
        }
        //if(!isset($possible_types[$int_to]))
        //{
        //    parse_error("$int_to ($int_to_type) is not a dict or list");
        //    continue;
        //}
        if (strpos(($to_contents = $disp_entities[$int_to]['contents']), $search_line)
            === false) {
            parse_error("cannot swap out $search_line from $int_to, it was not found");
            continue;
        }
        list($indented_item, $garbage) = explode(":", $search_line);
        $item = trim($indented_item);
        $base_indent = substr($indented_item, 0, (-1) * strlen($item)) . "  ";
        if ($new_file)
            $base_indent = $new_lines = "";
        else
            $new_lines = "$indented_item:\n";
        $count = 0;
        $first_line_indent = $subs_from_type == "list" ? "- " : "";
        $subsequent_line_indent = $subs_from_type == "list" ? "  " : "";

        foreach ($disp_entities[$subs_from]['subs'] as $entity_name => $entity) {
            $raw_lines = raw_lines_from_string($entity['contents']);
            for ($i = 0; $i < count($raw_lines); $i++) {
                $this_indent = $base_indent . (!$i ? $first_line_indent : $subsequent_line_indent);
                $new_lines .= $this_indent . $raw_lines[$i] . "\n";
            }
            //$new_lines .= "\n";
            $count++;
        }
        $disp_entities[$subs_from]['subs'] = array();
        if ($new_file) {
            $disp_entities[$int_to]['new_file'] = $new_file;
            $disp_entities[$int_to]['new_file_contents'] = $new_lines;
        } else
            $disp_entities[$int_to]['contents'] = str_replace($search_line, $new_lines, $to_contents);

    }

}
function nesting_in_progress($total_indent, $match)
{
    global $nesting_currently, $nest_completed, $cur_entity, $raw_line, $last_comment,
        $cur_integration, $raw_lines;

    $name = $match['text'];
    $entity_type = $match['type'];
    if ($name != $nesting_currently['name']) {
        $nesting_currently = array();
    }


    if (!$nesting_currently) {
        $base_path = $dir_name = $relative_path = $include = $new_path = "";
        build_nest_line($match, $base_path, $dir_name, $relative_path, $include, $new_path);


        list($line, $include_string) = explode(":", $raw_line, 2);
        if (trim($include_string)) {
            add_to_missing($name, $entity_type, $base_path);
            //parse_error("nesting_in_progress Config Error $name cannot be separated, it is already separated: $raw_line");
            //return false;
        }

        $nesting_currently['indent'] = $total_indent;
        $nesting_currently['name'] = $name;
        $nesting_currently['path'] = $new_path;

        if ($entity_type == "configuration")
            return true;

        $line_prefix = $match['disabled'] ? DISABLED_ITEM_PREFIX : "";
        $entities_inline = hypothetical_integration_type($name, 'entities_inline');

        $raw_line = "$line_prefix$line: $include $relative_path";
        if ($entities_inline)
            add_to_inline($name, $cur_integration['name'], $raw_line);


        $raw_lines[] = "#*# DEFINING FAKE INTEGRATION $name:";
        $raw_lines[] = "#*# ITEMS SHOULD BE PLACED IN $new_path";
        //$raw_lines[] = "#*# Relative:  $relative_path";
        $raw_lines[] = "#*# ORIGINATOR:  $base_path";
        $raw_lines[] = "$name:";


        if ($cur_entity)
            commit_line($cur_entity); // just FYI - homeassistant does not support this yet
        else
            commit_line($cur_integration);

        return true;
        //build me an army worthy of MORDOR!
    }

    rem_indent($raw_line, $nesting_currently['indent']);

    //$raw_lines[] ="#*# DEFINING FAKE INTEGRATION $name:";
    $raw_lines[] = $last_comment;
    $raw_lines[] = "$raw_line";

    $raw_line = $last_comment = "";

    return true;

}
function make_include_behave($type, $custom_location = false, $raw_line = false)
{
    if (!$raw_line)
        global $raw_line;

    list($indented_name, $include_data) = explode(":", $raw_line, 2);

    $match = array(
        "text" => trim($indented_name),
        "type" => $type,
        "entities_location" => $custom_location);

    $base_path = $dir_name = $relative_path = $include = $new_path = "";
    build_nest_line($match, $base_path, $dir_name, $relative_path, $include, $new_path);
    $raw_line = "$indented_name: $include $relative_path";
    return $raw_line;
}
function build_nest_line($match, &$base_path, &$dir_name, &$relative_path, &$include,
    &$new_path)
{
    global $cur_integration, $cur_entity;

    $entity_type = $match['type'];
    $name = $match['text'];
    $custom_location = $match['entities_location'];

    $dir_name = str_replace("/", "_", $name);

    if ($cur_entity) {
        $base_path = $cur_integration['entities_path'] . "/-unnamed.yaml";
        if ($ent_name = name_that_entity()) {
            $dir_name = $cur_integration['name'] . "_" . $ent_name;
            $base_path = $cur_integration['entities_path'] . "/$ent_name.yaml";

        }

    } else {
        $base_path = $cur_integration['integration_path'];
    }
    $new_path = $custom_location ? "$custom_location" : ENTITIES_LOCATION . "/$dir_name";

    switch ($entity_type) {
        case 'dict':
            $include = "!include_dir_merge_named";
            break;

        case 'list':
            $include = "!include_dir_merge_list";
            break;
        default:
            $include = "#*# *** WARNING: CANNOT INCLUDE, I DO NOT KNOW WHAT THIS IS!!***";
            break;
    }

    $relative_path = getRelativePath($base_path, $new_path);

}
function return_indent_size($temp_line)
{
    if (substr($temp_line, 0, 4) == "  - ")
        return 4;
    if (substr($temp_line, 0, 2) == "  ")
        return 2;
    if (substr($temp_line, 0, 2) == "- ")
        return 2;

    return 0;

}
?>