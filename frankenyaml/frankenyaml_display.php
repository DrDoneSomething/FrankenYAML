<?php

/**
 * This is a page to fiddle with the disp_entities array (the BIG  array!)
 * it is even called by other files.
 * It displays the interface selected by post or passed variables

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/

defined("IN_YAML_HELPER") || die("not in helper");
require ("frankenyaml_display_functions.php");
if (!($disp_mode = pv_or_blank('new_disp_mode')))
    $disp_mode = pv_or_else('disp_mode', 'view_with_edit');

if ($mode == 'display') {
    ihide("mode", 'display');
    ihide("disp_mode", $disp_mode);
    $export_multivar = array(
        "new_mode" => "parse_input",
        "ptext_from_export" => 1,
        "new_integration" => "",
        "new_entity" => "");
    $export_label = "Back To Parse";
    $export_confirm = false;
    $standard_multivar = array("new_entity" => "", "new_integration" => "");
    $display_modes = array(
        "index" => "List all",
        "view_with_edit" => "View All Contents",
        "separate_into_files" => "Separate Into Files",
        "export_single_file" => "Export Single File",
        "back_to_parse" => array(
            $export_label,
            $export_multivar,
            $export_confirm));

    $save = pv_or_blank('save');
    if (($file_type = pv_or_else('new_integration', false)) === false)
        $file_type = pv_or_blank('integration');
    ihide("integration", $file_type);


    if (($entity = pv_or_else('new_entity', false)) === false)
        $entity = pv_or_blank('entity');
    ihide("entity", $entity);

    // For the navigation menu
    $totdm = 0;
    foreach ($display_modes as $value => $label) {
        if ($totdm)
            echo " || ";
        $totdm++;
        if ($disp_mode == $value)
            $label= "<b>$label</b>";
        if (is_array($label)) {
            list($label, $multivar, $confirm_msg) = $label;
            isubmit_multi($multivar, $label, $confirm_msg, 'button_list');
        } else {
            $multivar = array_merge($standard_multivar, array("new_disp_mode" => $value));

            isubmit_multi($multivar, $label, false, 'button_list');

            //isubmit('new_disp_mode', $value, $label, false, 'button_list');
        }

    }
    echo "<hr width=\"400\" align=\"left\"/>";

}
if (($item_command = pv_or_blank("item_command")))
    require ("frankenyaml_item_commands.php");

switch ($disp_mode) {
    case "export_single_file":
        echo '<h1>configuration.yaml (combined!):</h1>';

        //icheckbox("create_recommended", 1, true, "Create Recommended Directories");
        //     isubmit('disp_mode', "build_confirm", "LETS GET BUILDING!");
        //ihide("disp_mode", "view_with_edit");
        //isubmit('download_zip', "do_it", "DOWNLOAD");
        include_once ("frankenyaml_export_yaml.php");

        //echo string_to_pre($yaml_text);

        break;
    case "separate_into_files":
        echo '<h1>Separate Into Small Files</h1>';
        echo "And now, the moment you've all been waiting for: We will cut this yaml up into tiny bits and place it into directories!<br />";
        icheckbox("create_recommended", 1, true, "Create Recommended Directories");
        //     isubmit('disp_mode', "build_confirm", "LETS GET BUILDING!");

        $multivar = array("download_zip" => "do_it", "new_disp_mode" => "view_with_edit");
        isubmit_multi($multivar, "Download!");

        break;
    case "build_confirm";
        echo "mode disabled, I promise it works though!";
        //        build_all_files(true);
        break;
    case "view_single_in_parse":
        if (!$file_type)
            die(" cannot view single, no file type set");

        if (!isset($disp_entities[$file_type]))
            die("cannot view single, $file_type does not exist");

        echo "";
        $int = $disp_entities[$file_type];
        echo '<li><b>' . $int['name'];
        echo "</b><br />" . format_contents($int);
        if ($int['type'] != "setting") {
            echo '<ul>';
            foreach ($int['subs'] as $entity) {
                echo '<li><b>' . $entity['name'];
                echo "</b><br />" . format_contents($entity);
            }
            echo '</ul>';
        }
        echo '</li>';
        echo '</ul>';

        break;


    case "edit_single_integration":
        if (no_file_type("Edit Integration"))
            break;

        $int = $disp_entities[$file_type];

        $int = $disp_entities[$file_type];
        echo '<li><b>' . $int['name'];
        echo "</b><br />";
        echo '<ul>';


        $multivar = array(
            "item_command" => "edit_integration_contents",
            "new_integration" => $file_type,
            "new_entity" => "",
            "save" => "save");

        isubmit_multi($multivar, "Save");
        echo '<br />';
        itextarea("new_integration_contents", $int['contents']);
        echo '<br />';
        isubmit_multi($multivar, "Save");


        echo '</li>';

        break;


    case "edit_single_entity":
        if (no_entity("Edit Entity"))
            break;

        $int = $disp_entities[$file_type];


        $contents = $int['subs'][$entity]['contents'];
        echo "";
        echo '<li><b>' . $file_type;
        echo "</b><br />" . format_contents($int);
        if ($int['type'] != "setting") {
            echo '<ul>';

            $multivar = array(
                "item_command" => "edit_entity_contents",
                "new_integration" => $file_type,
                "new_entity" => $entity,
                "save" => "save");

            echo '<li><b>' . $entity;
            echo "</b><br />"; //<pre>" . nl2br($entity['contents']) . "</pre>";
            isubmit_multi($multivar, "Save");
            echo '<br />';
            itextarea("new_entity_contents", $contents);
            echo '<br />';

            isubmit_multi($multivar, "Save");
            echo '</ul>';
        }
        echo '</li>';


        echo '</ul>';

        break;


    case "view_single_with_edit":
    case "view_with_edit":
        $disp_array = array();
        $info_button = false;
        $int_zoom_button = true;
        $ent_zoom_button = true;
        if ($file_type && !no_file_type()) {
            $info_button = true;
            $thing = $disp_array[$file_type] = $disp_entities[$file_type];
            $name = "Showing Single Integration: {$thing['name']}";
            if ($entity && !no_entity()) {
                $ent_zoom_button = false;
                unset($disp_array[$file_type]['subs']);
                $thing = $disp_array[$file_type]['subs'][$entity] = $disp_entities[$file_type]['subs'][$entity];
                $name .= " -> Entity: {$thing['name']}";
            } else
                $int_zoom_button = false;


            echo "<h2>$name</h2>";
        } else {
            echo "<h2>Viewing ALL Integrations & Entities</h2>";
            $disp_array = $disp_entities;

        }
        if (pv_or_blank('download_zip')) {
            echo '<h3>DOWNLOADING SHORTLY...</h3>';
            echo 'Click <a href="' . zip_path() .
                '">here</a> if it does not, should be valid for a few minutes<hr width="200" />';
        }
        echo "TOTAL INTEGRATIONS: " . count($disp_entities) . '<br /><ul>';
        echo "";
        foreach ($disp_array as $file_type => $int) {
            if(!isset($int['name']))
            {
                echo "<li>$file_type.yaml is empty</li>\n";
                continue;
            }

            $disabled = $int['disabled'] ? " * DISABLED *" : "";
            $disabled_class = $disabled ? ' class="disabled" ' : ' class="display" ';
            echo '<li ' . $disabled_class . '><b>' . $int['name'] . "<br />";
            if ($disabled) {
                $multivar = array(
                    "item_command" => "enable_integration",
                    "new_integration" => $int['name'],
                    "new_entity" => "",
                    "save" => "save");
                isubmit_multi($multivar, "Enable $file_type", false, "button_list");

            } else {
                $multivar = array(
                    "item_command" => "disable_integration",
                    "new_integration" => $int['name'],
                    "new_entity" => "",
                    "save" => "save");
                isubmit_multi($multivar, "Disable $file_type", false, "button_list");
            }
            $multivar = array(
                "item_command" => "download_single_integration",
                "new_integration" => $int['name'],
                "new_entity" => "",
                "save" => "save");
            isubmit_multi($multivar, "Download $file_type", false, "button_list");


            $multivar = array(
                "new_disp_mode" => "edit_single_integration",
                "new_entity" => "",
                "new_integration" => $file_type);
            isubmit_multi($multivar, "Edit $file_type", false, "button_list");


            $multivar = array(
                "item_command" => "delete_item",
                "new_entity" => "",
                "save" => "save",
                "new_integration" => $file_type);
            $confirm = "ARE YOU SURE YOU WANT TO DELETE $file_type, THIS WILL DELETE ITS SUBS TOO!";
            isubmit_multi($multivar, "Delete $file_type", $confirm, "button_list");
            if ($info_button) {
                $but_arr = $int;
                unset($but_arr['subs']);
                unset($but_arr['contents']);
                popup_button("More Info", $but_arr, "Integration $file_type data:",
                    "button_list");
            }

            if ($int_zoom_button) {
                $label = $info_button ? "Zoom In" : "Zoom In for More info";
                $multivar = array(
                    "new_disp_mode" => "view_single_with_edit",
                    "new_integration" => $file_type,
                    "new_entity" => "");
                isubmit_multi($multivar, $label, false, "button_list");

            }
            if ($int['type'] == "dict" || $int['type'] == "list") {

                $multivar = array(
                    "new_mode" => "parse_input",
                    "ptext_from_export" => 1,
                    "parse_mode" => $int['type'],
                    "new_integration"=>$file_type,
                    "new_entity" => "");
                isubmit_multi($multivar, "Create New $file_type", false, "button_list");

            }

            echo "</b><br />" . format_contents($int);
            if ($int['type'] != "setting") {
                echo '<ul>';
                foreach ($int['subs'] as $entity => $this_entity) {

                    $disabled = $this_entity['disabled'] ? " * DISABLED *" : "";
                    $disabled_class = $disabled ? ' class="disabled" ' : ' class="display" ';
                    echo '<li ' . $disabled_class . '><b>' . $entity . "<br />";
                    if ($disabled) {
                        $multivar = array(
                            "item_command" => "enable_entity",
                            "new_integration" => $int['name'],
                            "new_entity" => $entity,
                            "save" => "save");
                        isubmit_multi($multivar, "Enable $entity", false, "button_list");

                    } else {
                        $multivar = array(
                            "item_command" => "disable_entity",
                            "new_integration" => $int['name'],
                            "new_entity" => $entity,
                            "save" => "save");
                        isubmit_multi($multivar, "Disable $entity", false, "button_list");
                    }
                    $multivar = array(
                        "item_command" => "download_single_entity",
                        "new_integration" => $int['name'],
                        "save" => "save",
                        "new_entity" => $entity);
                    isubmit_multi($multivar, "Download $entity", false, "button_list");

                    $multivar = array(
                        "new_disp_mode" => "edit_single_entity",
                        "new_integration" => $int['name'],
                        "new_entity" => $entity);
                    isubmit_multi($multivar, "Edit $entity", false, "button_list");


                    $multivar = array(
                        "item_command" => "delete_item",
                        "new_integration" => $int['name'],
                        "save" => "save",
                        "new_entity" => $entity);
                    $confirm = "ARE YOU SURE YOU WANT TO DELETE $entity?";
                    isubmit_multi($multivar, "Delete $entity", false, "button_list");


                    if ($info_button) {
                        $but_arr = $this_entity;
                        unset($but_arr['contents']);
                        popup_button("More Info", $but_arr, "Integration $file_type -> $entity data:",
                            "button_list");
                    }

                    if ($ent_zoom_button) {
                        $label = $info_button ? "Zoom In" : "Zoom In for More info";
                        $multivar = array(
                            "new_disp_mode" => "view_single_with_edit",
                            "new_integration" => $file_type,
                            "new_entity" => $entity);
                        isubmit_multi($multivar, $label, false, "button_list");

                    }

                    echo "</b><br />" . format_contents($this_entity);
                }
                echo '</ul>';
            }
            echo '</li>';
            if (isset($int['new_file']) && $int['new_file']) {
                echo "<li>{$int['new_file']}<br />";
                echo string_to_pre($int['new_file_contents']) . "</li>";
            }

        }
        echo '</ul>';
        break;

        break;

    case "view_all":

        echo "TOTAL INTEGRATIONS: " . count($disp_entities) . '<br /><ul>';
        $html = "";
        foreach ($disp_entities as $key_name=> $int) {
            if(!isset($int['name']))
            {
                $html .= "<li>$key_name.yaml is empty</li>\n";
                continue;
            }
            $disabled = $int['disabled'] ? " * DISABLED *" : "";
            $disabled_class = $disabled ? ' class="disabled" ' : ' class="display" ';
            $html .= '<li ' . $disabled_class . '><b>' . $int['name'];
            $html .= "</b><br />" . format_contents($int);
            if ($int['type'] != "setting") {
                $html .= '<ul>';
                foreach ($int['subs'] as $entity) {
                    $disabled = $entity['disabled'] ? " * DISABLED *" : "";
                    $disabled_class = $disabled ? ' class="disabled" ' : ' class="display" ';

                    $html .= '<li ' . $disabled_class . '><b>' . $entity['name'];
                    $html .= "</b><br />" . format_contents($entity);
                }
                $html .= '</ul>';
            }
            $html .= '</li>';

        }
        $html .= '</ul>';
        echo $html;
        break;

    default:
    case "index":
        echo "TOTAL INTEGRATIONS: " . count($disp_entities) . '<br /><ul>';
        foreach ($disp_entities as $intname => $int) {
            echo '<li><b>';

            $multivar = array(
                "new_disp_mode" => "view_with_edit",
                "new_integration" => $intname,
                "new_entity" => "");
            isubmit_multi($multivar, $intname, false, "button_list");
            echo "</b><br />";

            echo '<ul>';
            foreach ($int['subs'] as $entname => $entity) {
                echo '<li>';

                $multivar = array(
                    "new_disp_mode" => "view_with_edit",
                    "new_integration" => $int['name'],
                    "new_entity" => $entname);
                isubmit_multi($multivar, $entity['name'], false, "button_list");
                echo "<br />"; //<pre>" . nl2br($entity['contents']) . "</pre>";
            }
            echo '</ul>';

            echo '</li>';

        }
        echo '</ul>';
        break;
}
if ($mode == "display" && $warnings = parse_error()) {
    popup_msg_on_load($warnings);
}
?>