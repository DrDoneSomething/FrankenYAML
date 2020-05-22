<?php

/**
 * @author Dr Done something
 * @copyright 2020
 * This is the main parse loop, it requires $ptext to be defined
 * It likes it when you offer $pm as parse mode - dict/list/configuration
 * also needs $file_type defined - it's either 'configuration' or the name
 * of an integration, typically.
 */

defined("IN_YAML_HELPER") || die("not in helper");

if (!$pm)
    die("no parsemode selected");

if (!$ptext)
    return;

$entities = array();

$raw_lines = raw_lines_from_string($ptext);
$total_real_lines = count($raw_lines);
$cur_integration = array();
$cur_entity = array();

switch ($pm) {
    case 'configuration':
        if ($file_type == 'configuration')
            $disp_entities = array();
        break;
    case 'setting':
    case 'dict':
    case 'list':

        if (!$file_type)
            die("no parsemode selected");
        build_integration($file_type);
        if (!$cur_integration) {
            die("No integration defined at BUILD");

        }

        //$disp_entities[$file_type]['subs'] = array();
        break;
    default:
        parse_error("No Parse Mode for file type: $file_type");
        return;
        break;
}

$last_comment = "";
$line_num = 0;
$ptext = "";
for ($raw_line = current($raw_lines); $raw_line !== false; $raw_line = next($raw_lines)) {

    $line_disabled = false;
    $line_num++;
    if (strpos($raw_line, "||") && is_numeric(substr($raw_line, 0, 4)))
        $raw_line = substr($raw_line, 6);


    if ($line_num > 9999) {
        parse_error("FATAL : cannot process more than 9999 lines");
        break;
    }
    if ($line_num < $total_real_lines)
        $ptext .= str_pad($line_num, 4, '0', STR_PAD_LEFT) . '||' . $raw_line . "\n";

    if (!trim($raw_line))
        continue;
    //disabled_item_prefix
    if (substr($raw_line, 0, strlen(DISABLED_ITEM_PREFIX)) == DISABLED_ITEM_PREFIX) {
        $raw_line = substr($raw_line, strlen(DISABLED_ITEM_PREFIX));
        $line_disabled = true;
    }

    if (substr(trim($raw_line), 0, 1) == "#") {
        $last_comment .= $raw_line . "\n";
        continue;

    }
    if ($pm == 'configuration') {

        if (substr($raw_line, 0, 2) == "  " && !$cur_integration) {
            parse_error("Entity has no parent, wrong parse mode selected ?");
            continue;
        }
        // new entity
        if (substr($raw_line, 0, 2) != "  ") {
            if ($cur_integration)
                commit_integration();
            list($integration_name, $one_liner) = explode(":", $raw_line, 2);
            $integration_name = trim($integration_name);
            $cur_integration['name'] = $integration_name;
            $cur_integration['generated'] = strpos($integration_name, "/") !== false;
            $cur_integration['contents'] = "";
            $cur_integration['disabled'] = $line_disabled;
            addcom($cur_integration);

            if (trim($one_liner)) {
                if (!$cur_integration['generated']) {
                    add_to_missing($integration_name);
                    //make_include_behave($cur_integration['type'], $cur_integration['entities_location']);
                }
            }
            build_integration();


            continue;
        }
    }
    if (!$cur_integration) {
        parse_error("No integration defined");
        continue;
    }


    if ($pm == 'configuration' && $cur_integration['type'] != 'setting')
        rem_indent($raw_line);


    if (check_nest())
        continue;
    switch ($cur_integration['type']) {
        case 'setting':

            if (substr($raw_line, 0, 2) != "  ") {
                parse_error("setting should be indented...");
                continue 2;
            }

            if (!$cur_integration['generated'])
                $raw_line = change_include_path($raw_line);
            commit_line($cur_integration);

            break;
        case 'dict':
            // new sub!
            if (substr($raw_line, 0, 2) != "  ") {
                if ($cur_entity)
                    commit_entity();
                if (strpos($raw_line, ":") === false) {
                    parse_error("Dictionary item has no semicolon after potential name");
                    continue 2;
                }
                list($entity_name, $should_be_blank) = explode(":", $raw_line, 2);
                $entity_name = trim($entity_name);
                if (!$entity_name) {
                    parse_error("No name found for this entity");
                    continue 2;
                }
                if ($should_be_blank) {
                    parse_error("Not sure what this crap '$should_be_blank' is after the semicolon. For a dictionary, it's not supposed to be here");
                    continue 2;
                }

                $cur_entity['name'] = $entity_name;
                $cur_entity['contents'] = "";
                $cur_entity['disabled'] = $cur_integration['disabled'] ? true : $line_disabled;

                $cur_entity['fields']['integration_name'] = $cur_integration['name'];
                $cur_entity['fields']['integration_type'] = $cur_integration['type'];
            }

            if (!$cur_entity) {
                parse_error("This line is not contained within an established entity");
                continue 2;
            }

            commit_line($cur_entity);
            break;

        case 'list':
            // new subs start with -
            if (substr($raw_line, 0, 2) == "- ") {
                if ($cur_entity)
                    commit_entity();
                $cur_entity['contents'] = "";
                $cur_entity['disabled'] = $cur_integration['disabled'] ? true : $line_disabled;
            }
            rem_indent($raw_line);
            add_to_list_entity_fields();

            commit_line($cur_entity);

    }

}
commit_disp();

?>