<?php

defined("IN_YAML_HELPER") || die("not in helper");


function line_disabled(&$raw_line)
{
    $line_disabled = false;
    if (substr($raw_line, 0, strlen(DISABLED_ITEM_PREFIX)) == DISABLED_ITEM_PREFIX) {
        $raw_line = substr($raw_line, strlen(DISABLED_ITEM_PREFIX));
        $line_disabled = true;
    }
    return $line_disabled;
}

function remint($file_type = false)
{
    global $big_array;
    if (!$file_type)
        global $file_type;
    if (!$file_type)
        die("no file type secified for remint");

    unset($big_array[$file_type]);
}
function rement($entname = false, $file_type = false)
{
    global $big_array;
    if (!$file_type)
        global $file_type;
    if (!$entname)
        global $entname;

    if (!$file_type)
        die("no file type secified for rement");
    if (!$entname)
        die("no entname secified for rement");
    if (is_array($entname))
        $entname = $entname['name'];
}
function yt($str, $prefix = false, $disabled = "")
{
    global $yaml_text;
    if ($disabled)
        $disabled = DISABLED_ITEM_PREFIX;
    $prefix !== false ? $str = "$prefix$str" : "";
    $yaml_text .= "$disabled$str\n";
}
function check_line()
{
    global $missing_files, $raw_line, $cur_entity, $cur_integration;
    if (strpos($raw_line, "!include") === false)
        return false;

    list($indented_name, $include_data) = explode(":", $raw_line);

    $search = array(
        '!include ' => 0,
        '!include_dir_list ' => 'list', // each file has a single list item
        '!include_dir_named ' => 'dict', // annoying pain in the dict - file name IS the base thingy
        '!include_dir_merge_list ' => 'list', // list merge with each item has a dash
        '!include_dir_merge_named ' => 'dict'); // can contain any amount of DICTS

    $path = "";
    $output = "";
    $found_type = "none";


    foreach ($search as $incstr => $found_type) {
        if (strpos($raw_line, $incstr)) {
            list($entity, $path) = explode($incstr, $raw_line);
            $path = trim($path);
            break;
        }
    }
    $name = trim($indented_name);
    if (!$entity) {
        parse_error("Could not add $name to missing, include method is not recognized.. ");
        return;

    }
    if (!$path) {
        parse_error("Could not add $name to missing, no path found $output...\n\n");
        return;
    }
    if (!$found_type) {
        parse_error("Include ignored: $raw_line", true,false);
        return false;
    }

    return array(
        $name,
        $path,
        $found_type);
}
function dump_subs($cur_integration = false, $passed_indent_string = "", $passed_disabled = false)
{
    global $cur_entity, $raw_line, $big_array, $nest_is_this_it;
    if (!$cur_integration)
        global $cur_integration;
    if ($cur_entity) {
        parse_error("We are in the middle of an entity! This might not work..", true);
    }
    $new_indent_first_line = ($cur_integration['type'] == "dict" ? "  " : "  - ");
    foreach ($cur_integration['subs'] as $entity_name => $cur_entity) {

        $contents = $cur_entity['contents'];
        $raw_lines = raw_lines_from_string($contents);
        $first_line_displayed = false;
        for ($i = 0; $i < count($raw_lines); $i++) {
            $raw_line = $raw_lines[$i];

            $is_generated_comment = (substr(($trl = trim($raw_line)), 0, 3) == "#*#");
            if ($is_generated_comment)
                continue;


            $line_disabled = line_disabled($raw_line);
            $disabled = $cur_integration['disabled'] || $cur_entity['disabled'] || $passed_disabled ||
                $line_disabled ? true : false;

            $is_comment = (substr(($trl = trim($raw_line)), 0, 1) == "#");
            if (!$trl)
                continue;

            $is_first_line = false;
            if (!$first_line_displayed) {
                if (!$is_comment && $trl) {
                    $first_line_displayed = true;
                    $is_first_line = true;
                }
            }

            $other_lines_indent = ($cur_integration['type'] == "dict" ? "  " : "    ");
            $new_indent = $passed_indent_string . ($is_first_line ? $new_indent_first_line :
                $other_lines_indent);
            $raw_line = "$new_indent$raw_line";
            if ($is_comment) {
                yt($raw_line, "", $disabled);
                continue;
            }

            list($level_count, $total_size, $indent_string) = export_return_indent_size($raw_line);
            export_check_nest();
            $name = false;
            if (strpos($raw_line, "!include"))
                list($name, $path, $found_type) = check_line();

            if ($name) { // WE FOUND A NESTED THINGY@!@ DO NOT PANIC!
                $nested_name = $nest_is_this_it;
                if (!isset($big_array[$nested_name])) {
                    parse_error("I found a nested thingy without an integration '$nested_name'");
                    continue;
                }
                if ($found_type != $big_array[$nested_name]['type'])
                    parse_error("Warning: found type is $type, this is a {$big_array[$nested_name]['type']}, going with stored", true);
                $raw_line = "$indent_string$name:";

                yt($raw_line, "", $disabled);
                dump_subs($big_array[$nested_name], $indent_string, $disabled);

                remint($nested_name);
                continue;
            }
            yt($raw_line, "", $disabled);


        }

        yt("");
    }
    $cur_entity = array();

}
function export_return_indent_size($temp_line)
{
    $level_count = 1;
    $indent_string = "";
    for ($total_size = 0; $new_size = get_indent($temp_line); $temp_line = substr($temp_line,
        $total_size)) {
        $indent_string .= substr($temp_line, 0, $new_size);
        $total_size += $new_size;
        $level_count++;
    }
    return array(
        $level_count,
        $total_size,
        $indent_string);
}
function get_indent($temp_line)
{
    if (substr($temp_line, 0, 4) == "  - ")
        return 4;
    if (substr($temp_line, 0, 2) == "  ")
        return 2;
    if (substr($temp_line, 0, 2) == "- ")
        return 2;

    return 0;
}
// I EXPECT YOU TO BE INDENTED!
function export_check_nest()
{
    global $cur_entity, $cur_integration, $raw_line, $nest_is_this_it, $last_comment;
    $max_en = 20;

    if (!$nest_is_this_it)
        $nest_is_this_it = $cur_integration['name'];


    if (substr($raw_line, 0, 2) != "  ") {
        return false;
        //parse_error("check nest is confused -- should have an indent here. Maybe the yaml was flawed, maybe check nest called at wrong time");
    }
    $temp_line = $raw_line;


    $cur_path = explode("/", $nest_is_this_it);
    if ($cur_path[0] != $cur_integration['name']) {
        $nest_is_this_it = $cur_integration['name'];
        $cur_path = array($cur_integration['name']);
    }


    $cur_level = count($cur_path);
    $prev_nest_info = "Prev Level: $cur_level prev path: '$nest_is_this_it'";
    $total_indent = 0;

    list($level_count, $total_indent, $indent_string) = export_return_indent_size($temp_line);

    if (!strpos($temp_line, ":"))
        $entity_name = "";
    else {

        $entity_name = str_replace(array(" ", "/"), array("", "_"), $temp_line);

        list($entity_name, $crap) = explode(":", $entity_name);

        if (strlen($entity_name) > $max_en)
            $entity_name = substr($entity_name, 0, $max_en);
    }

    while ($cur_level > $level_count) {
        //$raw_line .= "\n|removing ".$cur_path[$cur_level - 1]."|";
        unset($cur_path[$cur_level - 1]);
        $cur_level = count($cur_path);
    }
    if ($cur_level == $level_count) {
        //$raw_line .= "\n|swapping ".$cur_path[$cur_level - 1]." for $entity_name ti:'$total_indent' is:'$indent_string' lc:$level_count|";
        // the last cur path should equal entity_name, otherwise swap
        if ($cur_path[$cur_level - 1] != $entity_name) {
            $cur_path[$cur_level - 1] = $entity_name;
        }
    }
    if ($cur_level < $level_count) {
        if ($level_count > $cur_level + 1) {

            //parse_error("check nest got a problem here: We missed a level..");
        }
        //$raw_line .= "\n|adding  $entity_name|";
        $cur_path[] = $entity_name;
        $cur_level = count($cur_path);
    }

    $nest_is_this_it = implode("/", $cur_path);

    return;

}
/*
function export_integ_val($key, $cur_integration = false)
{
    if (!$cur_integration)
        global $cur_integration;
    if (!isset($cur_integration) || !$cur_integration)
        return false;
    if (!isset($cur_integration[$key]))
        return false;
    return $cur_integration[$key];
}*/


?>