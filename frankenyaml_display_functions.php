<?php
defined("IN_YAML_HELPER") || die("not in helper");

/**
 * Used by display.php and item commands (which is called by display.php)

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/
function format_contents($array, $echo = false)
{
    if (!($contents = $array['contents']))
        return $contents;

    $contents = string_to_pre($contents);
    if ($array['special_line']) {
        $raw_lines = raw_lines_from_string($contents);
        $contents = "";
        $search = string_to_pre(DISABLED_ITEM_PREFIX, false, false);
        foreach ($raw_lines as $key => $raw_line) {
            if (substr($raw_line, 0, strlen($search)) == $search) {
                $raw_line = '<mark class="disabled">' . substr($raw_line, strlen($search)) .
                    '</mark>';

            }
            $raw_lines[$key] = $raw_line;
        }
        $contents = implode("\n", $raw_lines);
    }
    if ($echo)
        echo $contents;

    return $contents;
}
function no_file_type($action_label = false)
{
    global $file_type, $disp_entities;
    $fail = false;
    if (!isset($file_type))
        $fail = $file_type = "unknown";
    if (!$fail && !$file_type)
        $fail = $file_type = "unknown";
    if (!$fail && !isset($disp_entities))
        $fail = true;
    if (!$fail && !$disp_entities)
        $fail = true;
    if (!$fail && !isset($disp_entities[$file_type]))
        $fail = true;
    if (!$fail)
        return false;

    if ($action_label)
        parse_error("$action_label failed, File type '$file_type' does not exist");
    return true;
}
function no_entity($action_label = false)
{
    if (no_file_type($action_label))
        return true;

    global $file_type, $disp_entities, $entity;
    $fail = false;
    if (!isset($entity))
        $fail = $entity = "unknown";
    if (!$fail && !$entity)
        $fail = $entity = "unknown";
    if (!$fail && !isset($disp_entities[$file_type]['subs'][$entity]))
        $fail = true;
    if (!$fail && !$disp_entities[$file_type]['subs'][$entity])
        $fail = true;
    if (!$fail)
        return false;
    if ($action_label)
        parse_error("$action_label failed, Entity '$entity' does not exist within File type '$file_type'.");
    return true;

}

?>