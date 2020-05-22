<?php

defined("IN_YAML_HELPER") || die("not in helper");


function build_dir($uid = false)
{
    if (defined("BUILD_DIR_UNIQUE"))
        return BUILD_DIR_UNIQUE;

    if (!BUILD_DIR)
        die("CONFIG ERROR: build_dir not set");
    if (!file_exists(BUILD_DIR)) {
        mkdir(BUILD_DIR);
        file_put_contents(BUILD_DIR . "/index.html", "hip hop and you don't stop");
    }
    $uid = $uid ? $uid : uid();

    define("BUILD_DIR_UNIQUE", BUILD_DIR . "/$uid");
    return BUILD_DIR_UNIQUE;

}

function build_file($path, $text = false, $uid = false)
{
    global $build_fail_state;
    if (!isset($build_fail_state))
        $build_fail_state = false;
    $uid = $uid ? $uid : uid();
    if (!build_dir($uid))
        die("no build dir");

    if (strpos($path, build_dir($uid)) !== 0)
        $path = build_dir($uid) . "/$path";
    if (strpos($path, "../") !== false || strpos($path, "/..") !== false || substr($path,
        0, 1) == "/" || strpos($path, "\\") !== false)
        die("$path has ../ or /.. or starts with / or has \ ");
    $dir_struct = explode("/", $path);
    $new_path = "";
    for ($i = 0; ($t = count($dir_struct)) > $i; $i++) {
        if ($i + 1 == $t || !$dir_struct[$i]) {
            // make dougnuts!
            break;
        }
        $new_path .= $dir_struct[$i];
        if (!make_dir($new_path)) {
            echo "could not make $path, stalled out at $new_path<br />";
            $build_fail_state = true;
            return false;
        }
        $new_path .= "/";
    }
    if ($text) {
        if (fn_not_yaml($path) && fn_not_html($path) && fn_not_txt($path) &&
            fn_not_save_file($path))
            die("TRICKERY! must be .yaml $path");
        if (file_put_contents($path, $text))
            return true;
        else {
            $build_fail_state = true;
            return false;
        }
    }

    return true;

    //echo "$path created!<br />";

}

function make_dir($path)
{
    if (file_exists($path)) {
        if (is_dir($path))
            return true;
        die("$path is not dir");
    }
    $result = @mkdir($path);
    return $result;
}
function rrmdir($dir = false, $verbose = false, $test_run = false, $override_last_modified = false)
{
    $test_run = true;
    if ($dir === false) {

        if (!($dir = build_dir()))
            die("no build dir");
    }

    if (strpos($dir, BUILD_DIR) !== 0)
        die("path '$dir' not within build dir " . BUILD_DIR);

    $ds = "/"; //DIRECTORY_SEPARATOR
    if (!is_dir($dir))
        die("rrmdir $dir is not a directory. I'm erroring out because who knows what you're trying to do to me (YOU BETTA THINK!).");

    $objects = scandir($dir);
    $skipped_files = false;
    $second_list = array();
    foreach ($objects as $object) {
        if ($object == "." || $object == "..")
            continue;
        $path = $dir . $ds . $object;
        if (is_dir($path) && !is_link($path)) {
            if (!rrmdir($path, $verbose, $test_run))
                $skipped_files = true;
        } else {
            if (keep_file($path, false, $override_last_modified) === "if_empty") {
                $second_list[] = $path;
                continue;
            }
            if (keep_file($path, false, $override_last_modified) === false) {
                if (!$test_run)
                    unlink($path);
                else
                    $path .= " (Just kidding)";
                if ($verbose)
                    funlink($path);

            } else {
                $skipped_files = true;
                continue;
            }
        }

    }
    foreach ($second_list as $path) {
        if (is_dir($path) && !is_link($path)) {
            die("rrmdir error: $path is a dir in the second list. Erroring because this is a bug");
        } else {
            if (keep_file($path, $skipped_files, $override_last_modified) === false) {
                if (!$test_run)
                    unlink($path);
                else
                    $path .= " (Just kidding)";
                if ($verbose)
                    funlink($path);
            } else {
                $skipped_files = true;
                continue;

            }
        }
    }
    if (!$skipped_files) {
        usleep(300);
        if (!$test_run)
            rmdir($dir);
        else
            $dir .= " (Just kidding)";
        if ($verbose)
            frmdir($dir);
    }
    if ($verbose && $skipped_files)
        echo "rrmdir not deleted because files were skipped '" . implode("', '", $second_list) .
            "'<br />";
    return $skipped_files ? false : true;
}
function keep_file($path, $skipped_files = "not_set", $override_last_modified = false)
{
    $last_modified = time() - filemtime($path);
    if ($override_last_modified)
        $last_modified = time() * 10;

    if ($last_modified < (PRUNE_OLDER_THAN_MINUTES * 60))
        return true;
    // You know what? Fuck links.
    if (is_link($path))
        return false;
    $delete_files = array(
        ".yaml" => "always",
        ".zip" => "always",
        ".html" => "if_empty");
    if ($last_modified > (IDLE_ACCOUNT_DELETE_DAYS * 60 * 60 * 24))
        $delete_files[SAVE_FILENAME] = "always";
    foreach ($delete_files as $extension => $condition) {
        if (basename($path) === $extension || strtolower(substr($path, (-1 * strlen($extension)))) ==
            $extension) {
            switch ($condition) {
                case "always":
                    return false;
                case "if_empty":
                    if ($skipped_files === "not_set")
                        return "if_empty";
                    elseif ($skipped_files === false)
                        return false;
                    else
                        return true;
                default:
                    die("Condition $condition not recognized in keep_file function (bug)");
            }
        }
    }
    return true;
}
function fn_not_yaml($str)
{
    return (stripos(substr($str, -5), ".yaml") !== 0);
}
function fn_not_zip($str)
{
    return (stripos(substr($str, -4), ".zip") !== 0);
}
function fn_not_txt($str)
{
    return (stripos(substr($str, -4), ".txt") !== 0);
}
function fn_not_html($str)
{
    return (stripos(substr($str, -5), ".html") !== 0);
}
function fn_not_save_file($str)
{
    // we double check this
    if (basename($str) !== SAVE_FILENAME)
        return true;
    return substr($str, (-1 * strlen(SAVE_FILENAME))) !== SAVE_FILENAME;
}
function funlink($str)
{
    echo "Del file: $str<br />";
}
function frmdir($str)
{
    echo "Del dir: $str<br />";

}
function dliframe($filename, $content)
{
    $dlurl = './download_base64.php/' . $filename . '?' . base64_encode($content);
    echo '<iframe width="10" height="10" src="' . $dlurl . '"></iframe>';

}
function download_zip($echo = false)
{
    build_zip();
    head_tag_url_set(zip_path());

}
function create_zip_from_files()
{
    // Get real path for our folder
    $rootPath = realpath(build_dir());
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open(zip_path(), ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Initialize empty "delete list"
    $filesToDelete = array();

    // Create recursive directory iterator
    /**
     *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  * @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            if (fn_not_yaml($fn = $file->getFilename())) {
                echo "file skipped: $fn<br />";
                continue;
                //$filesToDelete[] = $filePath;
            }
            // Add current file to archive
            $zip->addFile($filePath, $relativePath);

            // Add current file to "delete list"
            // delete it later cause ZipArchive create archive only after calling close function and ZipArchive lock files until archive created)
            if ($file->getFilename() != 'important.txt') {
                //$filesToDelete[] = $filePath;
            }
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

    // Delete all files from "delete list"
    foreach ($filesToDelete as $file) {
        unlink($file);
    }
}
function build_all_files($echo = false, $die_on_fail = true)
{
    global $disp_entities, $build_fail_state;
    $build_fail_state = false;
    $build_all_files_dir = build_dir() . "/" . BUILD_ALL_FILES_DIR;
    rrmdir($build_all_files_dir);
    $build_all_files_dir .= "/";
    $html = "";
    foreach ($disp_entities as $int) {
        //usleep ( 100 );
        $html .= '<li><b>' . $int['name'];
        $html .= "</b><br />";
        $int_path = $int['integration_path'];
        if ($int['disabled'])
            $html .= " DISABLED, SKIPPED";
        elseif (!$int['generated'])
            $html .= build_file($build_all_files_dir . $int_path, $int['contents']) ?
                'SUCCESS!' : "FAIL!";
        $html .= "<br />";
        if (!$int['disabled'] && pv_or_blank('create_recommended') && $int['type'] !==
            'setting')
            $html .= build_file($build_all_files_dir . $int['entities_path']) ?
                'ENTITYS FOLDER SUCCESS!' : "ENTITYS FOLDER FAIL!";

        $html .= " -> $int_path";
        $html .= '<ul>';
        foreach ($int['subs'] as $entity) {
            //usleep ( 100 );
            $html .= '<li>' . $entity['name'];
            $html .= "<br />"; //<pre>" . nl2br($entity['contents']) . "</pre>";
            if ($int['disabled'] && !$entity['DISABLED'])
                $html .= " WARNING: integration was disabled, entity was not, skipping";
            elseif ($entity['disabled'])
                $html .= " DISABLED, SKIPPED";
            else
                $html .= build_file($build_all_files_dir . $entity['entity_path'], $entity['contents']) ?
                    'SUCCESS!' : "FAIL!";
            $html .= " -> {$entity['entity_path']}";
        }
        $html .= '</ul>';
        $html .= '</li>';

    }
    $html .= '</ul>';
    if ($die_on_fail && $build_fail_state)
        die("<h1>FAILED TO BUILD ALL FILES</h1>$html");
    if ($echo)
        echo $html;

    return !$build_fail_state;


}
function build_zip($echo = false, $die_on_fail = true)
{
    global $disp_entities, $build_fail_state;
    $build_fail_state = false;


    build_file("");

    $zip = new ZipArchive();
    $zip->open((zip_path()), ZipArchive::CREATE | ZipArchive::OVERWRITE);


    $html = "";
    foreach ($disp_entities as $int) {
        //usleep ( 100 );
        $html .= '<li><b>' . $int['name'];
        $html .= "</b><br />";
        $int_path = $int['integration_path'];
        if (!$int['generated'])
            $zip->addFromString($int_path, $int['contents']);

        $html .= "<br />";

        $html .= " -> $int_path";
        $html .= '<ul>';
        $subs_created = false;
        foreach ($int['subs'] as $entity) {
            $subs_created = true;
            //usleep ( 100 );
            $html .= '<li>' . $entity['name'];
            $html .= "<br />";

            $zip->addFromString($entity['entity_path'], $entity['contents']);
            $html .= " -> {$entity['entity_path']}";
        }
        $new_file = false;
        if (isset($int['new_file']) && $int['new_file']) {
            $new_file = true;
            if (substr($int['new_file'], -5) != ".yaml") {
                $html .= "COULD NOT DUMP NEW FILE CONTENTS {$int['new_file']}, IT WAS NOT A YAML FILE";
                break;
            }
            $zip->addFromString($int['new_file'], $int['new_file_contents']);
        }


        if (pv_or_blank('create_recommended') && $int['type'] !== 'setting' && !$subs_created &&
            !$new_file)
            zip_empty_dir($zip, $int);

        $html .= '</ul>';
        $html .= '</li>';

    }
    $html .= '</ul>';
    if ($die_on_fail && $build_fail_state)
        die("<h1>FAILED TO BUILD ALL FILES</h1>$html");
    if ($echo)
        echo $html;


    $zip->close();
    return !$build_fail_state;


}
function zip_path()
{
    $zippath = build_dir() . "/ziped.zip";
    return $zippath;
}
function zip_empty_dir(&$zip, $int)
{
    $path = $int['entities_path'];
    if (substr($path, -1) != "/")
        $path .= "/";
    $type = $int['type'];
    $path .= "Example_$type.README";
    $contents = array();
    $contents[] = "# THIS DIRECTORY WAS CREATED BECAUSE YOU ASKED ME TO";
    $contents[] = "# IN HERE YOU CAN PUT .yaml FILES AND THEY WILL AUTOMATICALLY BE LOADED BY HOME ASSISTANT";
    $contents[] = "# EACH CONTAINING A SINGLE $type for the {$int['name']} integration";
    $contents[] = "# IT SHOULD NOT MATTER THE FILENAME, AS LONG AS IT ENDS IN '.yaml'";
    $contents[] = "# DO NOT INDENT";
    switch ($type) {
        case 'configuration':
        case 'setting':
            $contents[] = "# EXAMPLE $type :  (This is probably not a good example, just watch the formatting!)";
            $contents[] = "homeassistant:";
            $contents[] = "  # Name of the location where Home Assistant is running";
            $contents[] = "  name: Home";
            $contents[] = "  # Location required to calculate the time the sun rises and sets";
            $contents[] = "  latitude: 22";
            $contents[] = "  longitude: -100";
            $contents[] = "  # Impacts weather/sunrise data (altitude above sea level in meters)";
            $contents[] = "  elevation: 500";
            $contents[] = "  # metric for Metric, imperial for Imperial";
            $contents[] = "  unit_system: metric";
            $contents[] = "  # Pick yours from here: http://en.wikipedia.org/wiki/List_of_tz_database_time_zones";
            $contents[] = "  time_zone: America/New Orleans";
            $contents[] = "  # Customization file";
            $contents[] = "  customize: !include_dir_merge_named ../customizations/entities/";
            break;
        case 'dict':
            $contents[] = "# EXAMPLE $type : input_boolean (This is probably not a good example, just watch the formatting!)";
            $contents[] = "htpc_status_display:";
            $contents[] = "  name: HTPC";
            $contents[] = "  icon: mdi:desktop-classic";
            break;
        case 'list':
            $contents[] = "# EXAMPLE $type : switch  (This is probably not a good example, just watch the formatting!)";
            $contents[] = "platform: mqtt";
            $contents[] = "name: \"Casita Doorbell Chime\"";
            $contents[] = "state_topic: \"state/casitadoorbell\"";
            $contents[] = "command_topic: \"casitadoorbell/commands\"";
            $contents[] = "payload_on: \"Doorbell Chime\"";
            $contents[] = "payload_off: \"Doorbell Silent\"";
            $contents[] = "icon: mdi:bell-ring-outline";
            $contents[] = "retain: false";
            break;
        default:
            $contents[] = "# OKAY, I WOULD LOVE TO SHOW YOU AN EXAMPLE OF A $type BUT I DO NOT KNOW WHAT THAT IS. THIS IS PROBABLY A SERIOUS BUG";
            $contents[] = "# I'M NOT SURE WHY I DON'T THROW AN ERROR HERE, YOLO PERHAPS";
            break;
    }


    return $zip->addFromString($path, implode("\n", $contents));
}
function prune_old_files()
{
    $dir = BUILD_DIR;
    $ds = "/"; //DIRECTORY_SEPARATOR

    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object == "." || $object == "..")
                continue;
            if (!is_dir($path = ($dir . $ds . $object)))
                continue;
            if (time() - filemtime($path) > (PRUNE_OLDER_THAN_MINUTES * 60))
                rrmdir($path);

        }
    }


}
function pretty_filename($filename)
{
    return ucwords(str_replace("_", " ", substr($filename, 0, -4)));
}
?>