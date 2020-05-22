<?php

/**
 * @author Dr Done something
 * @copyright 2020
 * This is IN BETA
 * It'll try and convert everything back to a yaml'
 */
defined("IN_YAML_HELPER") || die("not in helper");
require ("frankenyaml_export_yaml_functions.php");
$cur_integration = $cur_entity = $raw_line = $nest_is_this_it = "";
$yaml_text = "";
yt("EXPORTED CONFIGURATION.YAML", "#*# ");
yt("USE AT YOUR OWN RISK, THIS MAY GET MESSY!", "#*# ");

$big_array = $disp_entities;
unset($big_array['configuration']);
foreach ($big_array as $file_type => $cur_integration) {

    if ($cur_integration['generated'] || $cur_integration['name'] == "configuration")
        continue;

    yt("");

    $nest_is_this_it = "";
    $int_contents = $cur_integration['contents'];
    $loop_integration = $cur_integration;
    $cur_indent = "";

    $raw_lines = raw_lines_from_string($int_contents);

    foreach ($raw_lines as $line_num => $raw_line) {

        $is_generated_comment = (substr(($trl = trim($raw_line)), 0, 3) == "#*#");
        if ($is_generated_comment)
            continue;
        $cur_integration = $loop_integration;
        $name = $path = $found_type = false;
        $line_disabled = line_disabled($raw_line);
        if (substr($trl, 0, 1) == "#") {
            yt($raw_line);
            continue;
        }
        if (!$trl)
            continue;

        list($level_count, $total_size, $indent_string) = export_return_indent_size($raw_line);

        if (strpos($raw_line, "!include"))
            list($name, $path, $found_type) = check_line();
        export_check_nest();

        $disabled = $cur_integration['disabled'] || $line_disabled ? true : false;

        if (!$name) {
            yt($raw_line, false, $disabled);
            continue;
        }

        if ($name == $cur_integration['name'] && $level_count == 1) {
            if ($found_type != $cur_integration['type']) {
                parse_error("Warning: found type is $type, this is a {$cur_integration['type']}, going with stored", true);
            }
            if ($line_disabled && !$cur_integration['disabled'])
                parse_error("FYI: Line was disabled but integration was not, going to disable it", true);

            $raw_line = "$name:";
            yt($raw_line, false, $disabled);
            dump_subs(false, false, $disabled);
            continue;
        }
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

        // look for nested!

    }
    remint();
}
$cur_entity = $cur_integration = array();
if (count($big_array)) {
    unset($line_num);
    unset($raw_line);
    parse_error("Not all integrations have been, well, integrated. Probably nested" .
        " entities within disabled integrations (it's fine, it's all fine. Here are the  missing: \n<ul><li>" .
        implode("</li><li>", array_keys($big_array)) . "</li></ul>", true);
}
if (error_state()) {
    echo parse_error();
    return;
}
if (MODE == "parse_input")
    $ptext = $yaml_text;
else
    itextarea("", $yaml_text);
?>