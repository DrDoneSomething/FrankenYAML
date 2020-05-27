
<table width="100%">
<tr><td width="50%">
<?php
/**
 * This is the parse page... It runs the parse scripts and manages the file input

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/


defined("IN_YAML_HELPER") || die("not in helper");
require_once ("frankenyaml_parse_functions.php");

$all_ptext = pv_64_import("parse_input_all_ptext");


ihide("mode", "parse_input");

if (!($parse_input_mode = pv_or_blank('new_parse_input_mode')))
    $parse_input_mode = pv_or_else('parse_input_mode', 'edit');


if (($pm = pv_or_else('new_parse_mode', false)) === false)
    $pm = pv_or_blank('parse_mode');
    
$first_run = ($pm? false : true);
define("HAS_SAVED_ARRAY", (count($disp_entities) > 1 ? true : false));
$using_imported_yaml = pv_or_else("using_imported_yaml", false);


while (pv_or_blank("ptext_from_export")) {
    $first_run = false;
    if (!HAS_SAVED_ARRAY) {
        parse_error("I'm afraid I Can't do that, Dave:" .
            " I can't export the big array back into the parse script, it has no contents. Probably a bug :(");
        break;
    }
    if (false &&$pm) {
        parse_error("I'm afraid I Can't do that, Dave:" .
            " I can't export the big array back into the parse script, I think you are parsing something else. Probably a bug :(");
        break;
    }
    require_once ("frankenyaml_export_yaml.php");
    $all_ptext['configuration']['text'] = $ptext;
    $all_ptext['configuration']['pm'] = "configuration";
    $all_ptext['configuration']['file_type'] = "configuration";
    $all_ptext['configuration']['multiple_items'] = "";
    $ptext = "";

    $using_imported_yaml = true;
    break;
}

if ($first_run && !$using_imported_yaml && HAS_SAVED_ARRAY &&  $parse_input_mode != "ask_for_missing" )
    help_on_load("pm_load_from_saved", "Load configuration.yaml From Saved?");

if (HAS_SAVED_ARRAY || $using_imported_yaml)
    $first_run = false;
define("PARSE_MODE_FIRST_RUN", $first_run);
define("USING_IMPORTED_YAML", $using_imported_yaml);
ihide("using_imported_yaml", $using_imported_yaml);


if (($file_type = pv_or_else('new_integration', false)) === false)
    $file_type = pv_or_blank('integration');

if (!$file_type)
    $file_type = "configuration";
if ($file_type == "configuration")
    $pm = "configuration";

$current_file = $file_type;
$multiple_items = pv_or_blank("multiple_items");

$ptext = pv_or_blank('raw_parse_text');
$filename = pv_or_else("filename", $file_type);

if ($ptext) {
    $all_ptext[$filename]['text'] = $ptext;
    $all_ptext[$filename]['pm'] = $pm;
    $all_ptext[$filename]['file_type'] = $file_type;
    $all_ptext[$filename]['multiple_items'] = $multiple_items;
} elseif (isset($all_ptext[$filename])) {
    $ptext = $all_ptext[$filename]['text'];
    $pm = $all_ptext[$filename]['pm'];
    $file_type = $all_ptext[$filename]['file_type'];
    $multiple_items = $all_ptext[$filename]['multiple_items'];
}
isubmit("new_mode", "parse_settings", "Edit Parse Settings",
    "Going back to config will result in losing all the invidivdual parsed files," .
    " however the system may be able to reconstruct a single configuration.yaml from your parsed data" .
    " (provided nothing goes wrong). ARE YOU SURE?");
if (HAS_SAVED_ARRAY) {
    isubmit("ptext_from_export", 1, "Grab YAML From Parsed",
        "This will take your parsed/compiled YAML data and allow you to edit it as one single configuration.yaml file," .
        " provided it parsed correctly. It will also ERASE all parsed files and replace it with this one. ARE YOU SURE?", false,
        "ptext_from_export_button");
}
echo "<center><h2>$file_type.yaml</h2></center><hr width=\"200\" />";

switch ($parse_input_mode) {
    case 'ask_for_missing':
        if ($warnings = parse_error()) {
            popup_msg_on_load($warnings);
        }
    case 'parse_and_display':
        $config_found = false;
        foreach ($all_ptext as $filename => $post_entry) {
            $ptext = $all_ptext[$filename]['text'];
            $pm = $all_ptext[$filename]['pm'];
            $file_type = $all_ptext[$filename]['file_type'];
            $multiple_items = $all_ptext[$filename]['multiple_items'];
            $remove_from_missing = ($multiple_items ? false : true);
            if ($file_type == "configuration")
                $config_found = true;
            if (!$config_found) {
                parse_error("Cannot load $file_type, Configuration.yaml must be first");
                break;
            }
            $ptext = $post_entry['text'];
            $pm = $post_entry['pm'];
            include ("frankenyaml_parse_code.php");
            $all_ptext[$filename]['text'] = $ptext;
            if (error_state())
                break;
        }
        if (!error_state())
            execute_inlines();


        if (ask_for_missing())
            break;

        if (!dump_errors()) {
            $file_type = $current_file;
            include ("frankenyaml_display.php");


            div_open_float();

            $confirm_msg = "Are you sure? You will not be able to add any YAML files after this.";
            if ($missing_files)
                $confirm_msg .= " Also, you have " . count($missing_files) .
                    " missing files to add!";
            isubmit("new_mode", 'display', "Confirm Parsed Items", $confirm_msg);
            echo '<hr width="200" />';
            if ($missing_files) {
                echo "<br /><b>Missing Files:</b><br /><ul>";
                foreach ($missing_files as $file_type => $data) {
                    // it's an included directory! Hopefully!
                    $path = $data['path'];
                    $filename = $integration = $button_title = $file_type;
                    $multiple_items = false;
                    if ($data['found_type']) {
                        echo '<li>';
                        $multiple_items = true;
                        $button_title = "Add New $file_type";
                        if ($data['type'] == "configuration") {
                            $path = "";
                        }
                        $filename = "$file_type : File " . (count($all_ptext));

                        $multivar = array(
                            "parse_mode" => $data['type'],
                            "multiple_items" => $multiple_items,
                            "filename" => $filename,
                            "new_parse_input_mode" => "edit",
                            "integration" => $file_type);
                        isubmit_multi($multivar, $button_title, false, "button_list");

                        if ($path)
                            echo "<ul><li>$path</li></ul>";
                        echo '</li>';

                    } else {
                        echo "<li>$file_type<ul>";
                        if ($path)
                            echo "<li>$path</li>";

                        echo "<li>";
                        $multivar = array(
                            "parse_mode" => $data['type'],
                            "multiple_items" => $multiple_items,
                            "filename" => $filename,
                            "new_parse_input_mode" => "edit",
                            "integration" => $file_type);

                        isubmit_multi($multivar, $button_title, false, "button_list");

                        echo "</ul>";
                    }


                }
                echo '</ul><hr width="200" />';
            }


            echo "<br /><b>Parsed Files:</b><br /><ul>";

            foreach ($all_ptext as $filename => $post_entry) {
                echo "<li>";
                $pm = $post_entry['pm'];
                $multiple_items = $post_entry['multiple_items'];
                $file_type = $post_entry['file_type'];
                $dmode = $pm == "configuration" ? "view_all" : "view_single_in_parse";

                $multivar_common = array(
                    "parse_mode" => $pm,
                    "multiple_items" => $multiple_items,
                    "filename" => $filename,
                    "integration" => $file_type);

                $multivar = array_merge(array("new_parse_input_mode" => "parse_and_display",
                        "new_disp_mode" => $dmode), $multivar_common);

                isubmit_multi($multivar, $filename, false, "button_list");

                $multivar = array_merge(array("new_parse_input_mode" => "edit"), $multivar_common);
                isubmit_multi($multivar, "Edit", false, "button_list");
                echo "</li>";

            }
            div_close_float();
            break;
        } else
            echo '</td><td  width="50%">';

    case 'edit':
        $integration = $file_type;
        if ($multiple_items) {
            echo "<b>Paste non-indented SINGLE Item under type: $file_type<br />";
            //itext("filename",pv_or_blank("filename"),"Unique Filename (does not matter), just needs to be unique");
        } else
            echo "<b>File: $file_type.yaml ($pm)</b> <br /> Paste or type in the file contents for parsing!<br />";
        if (!$pm) {
            popup_msg_on_load("ERROR: invalid parse mode: $pm for $file_type");
            break;

        }
        ihide("new_parse_mode",$pm);
        ihide("new_integration",$integration);
        ihide_pv("filename");
        ihide_pv("multiple_items");

        if ($pm == 'configuration')
            ihide("disp_mode", "view_all");
        else
            ihide("disp_mode", "view_single_in_parse");

        isubmit("new_parse_input_mode", 'parse_and_display', "Parse!");
        echo "<br />";
        if ($ptext)
            echo "<b>NOTE: I MAY HAVE ADDED LINE NUMBERS, YOU CAN IGNORE THEM, THEY WILL NOT BE SAVED</b><br />";

        if (PARSE_MODE_FIRST_RUN)
            help_on_load("pm_edit", "Parse Text Entry");
        itextarea("raw_parse_text", $ptext);
        isubmit("new_parse_input_mode", 'parse_and_display', "Parse!");

        break;

    default:
        die("invalid parse input mode '$parse_input_mode'");
}


ihide_64_export("parse_input_all_ptext", $all_ptext);
?>
</td></tr>
</table>