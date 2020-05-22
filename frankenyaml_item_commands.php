<?php
/**
 * This MUST be called from display.php
 * it relies on variables and functions set by that file.

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/

defined("IN_YAML_HELPER") || die("not in helper");
if (!$save) {
    parse_error("Bug: item command $item_command not confirmed ");
    return;
}
switch ($item_command) {
    case "delete_item":

        if ($entity) {
            if (no_entity("Delete Entity"))
                return;
            $name = $disp_entities[$file_type]['name'];
            $name .= " - > " . $disp_entities[$file_type]['subs'][$entity]['name'];
            unset($disp_entities[$file_type]['subs'][$entity]);
            $entity = false;
        } else {

            if (no_file_type("Delete Integration"))
                return;
            $name = $disp_entities[$file_type]['name'];
            unset($disp_entities[$file_type]);
            $file_type = false;
        }
        parse_error("Deleted $name", "Success");
        break;
    case "disable_entity":
        if (no_entity("Disable Entity"))
            return;
        $disp_entities[$file_type]['subs'][$entity]['disabled'] = true;

        parse_error("Disabled $entity (in $file_type)", "Success");
        break;
    case "enable_entity":
        if (no_entity("Disable Entity"))
            return;
        if ($disp_entities[$file_type]['disabled']) {
            parse_error("Cannot enable $entity, parent $file_type is disabled");
            return;
        }
        $disp_entities[$file_type]['subs'][$entity]['disabled'] = false;

        parse_error("Enabled $entity (in $file_type)", "Success");
        break;
    case "disable_integration":
        if (no_file_type("Disable Integration"))
            return;
        $disp_entities[$file_type]['disabled'] = true;
        if (isset($disp_entities[$file_type]['subs']) && $disp_entities[$file_type]['subs']) {
            foreach ($disp_entities[$file_type]['subs'] as $ent_name => $sub) {
                $disp_entities[$file_type]['subs'][$ent_name]['disabled'] = true;
            }
        }
        parse_error("Disabled $file_type", "Success");

        break;
    case "enable_integration":
        if (no_file_type("Disable Integration"))
            return;
        $disp_entities[$file_type]['disabled'] = false;
        if (isset($disp_entities[$file_type]['subs']) && $disp_entities[$file_type]['subs']) {
            foreach ($disp_entities[$file_type]['subs'] as $ent_name => $sub) {
                $disp_entities[$file_type]['subs'][$ent_name]['disabled'] = false;
            }
        }
        parse_error("Enabled $file_type", "Success");

        break;

    case "edit_integration_contents":

        if (no_file_type("Edit Integration Contents"))
            return;
        if (($new_contents = pv_or_else("new_integration_contents", false)) === false) {
            parse_error("Could not edit $file_type/$entity, post val 'new_integration_contents' not set (bug)");
            return;
        }
        $disp_entities[$file_type]['contents'] = $new_contents;
        parse_error("Saved contents of $file_type", "Success");

        break;
    case "edit_entity_contents":

        if (no_entity("Edit Entity Contents"))
            return;
        if (($new_contents = pv_or_else("new_entity_contents", false)) === false) {
            parse_error("Could not edit $file_type/$entity, post val 'new_entity_contents' not set (bug)");
            return;
        }
        $disp_entities[$file_type]['subs'][$entity]['contents'] = $new_contents;

        parse_error("Saved contents of $entity within $file_type", "Success");
        break;
    case "download_single_entity":

        if (no_entity("Download Entity"))
            return;
        $entity_data = $disp_entities[$file_type]['subs'][$entity];

        $fn = $entity_data['entity_path'];
        dliframe($fn, $entity_data['contents']);
        parse_error("Your download of $entity within $file_type should begin shortly",
            "Success");
        break;
    case "download_single_integration":

        if (no_file_type("Download Integration"))
            return;
        $integration_data = $disp_entities[$file_type];

        $fn = $integration_data['integration_path'];
        dliframe($fn, $integration_data['contents']);
        parse_error("Your download of $file_type should begin shortly", "Success");
        break;
    default:
        parse_error("Bug: item command $item_command not found");
        break;
}

?>