<?php

defined("IN_YAML_HELPER") || die("not in helper");

function js_cmnd_reference($mode = "get_reference", $return_id = false, $cmnd = false)
{
    switch ($mode) {
        case "insert":
        case "update":
        case "receive_reference":
            if (!$return_id)
                js_die("FAIL js_cmnd_reference : no return id, cannot $mode");
            if ($cmnd === false)
                js_die("FAIL js_cmnd_reference : no cmnd, cannot $mode");

            $cmnd = json_encode($cmnd);

            // tasmota_manual_command(return_id,mode,cmnd)
            $return_id = addslashes($return_id);
            $js_command = "tasmota_manual_command('$return_id','$mode',$cmnd);";

            js_dump_line($js_command);

            return true;
        case "get_reference":
            return js_cmnd_receive_reference();
        default:
            js_die("FAIL js_cmnd_reference invalid mode $mode in");
            return false;
    }


}


// will take in a string or array of strings or an array of an array of an array of strings
// and append $append, unless there already
// then outputs a string!
// Appends always begin with _td_
function js_format_return_id($input, $append = "", $append_prefix = "")
{
    if ($append && (is_array($append) || stripos($append, JAVASCRIPT_ID_DELIMITER)
        !== false)) {
        // okay okay don't panic! append has multiple values too...
        $append_as_id = js_format_return_id($append);
        $appends = explode(JAVASCRIPT_ID_DELIMITER, $append_as_id);
        $new_id = array();
        foreach ($appends as $ap)
            $new_id[] = js_format_return_id($input, $ap, $append_prefix);
        return js_format_return_id($new_id);
    }
    if ($append && $append_prefix)
        if (strpos($append, $append_prefix) !== 0)
            $append = "$append_prefix$append";

    if (!is_array($input))
        $input = explode(JAVASCRIPT_ID_DELIMITER, $input);
    $clean_input = array();
    foreach ($input as $k => $id) {
        if (is_array($id))
            $id = js_format_return_id($id, $append);

        $split = explode(JAVASCRIPT_ID_DELIMITER, $id);
        foreach ($split as $id) {
            if ($append && strpos($id, "$append") === false)
                $id = "$id$append";

            $clean_input[$id] = $id;

        }
    }


    return implode(JAVASCRIPT_ID_DELIMITER, $clean_input);

}
function js_append_start($return_id, $append)
{
    js_start();
    $return_id = js_format_return_id($return_id, $append);
    echo 'append_to_inner(\'' . $return_id . '\',\'';
}
function js_append_end()
{
    echo "');\n";
    js_end();
}
function js_return_start($return_id, $append)
{
    js_start();

    $return_id = js_format_return_id($return_id, $append, "_td_");
    echo 'return_to_inner(\'' . $return_id . '\',\'';
}
function js_return_end()
{
    echo "');\n";
    js_end();
}
function js_refresh_page()
{
    js_start();
    echo "\nwindow.location.href='{$_SERVER['PHP_SELF']}';\n";
    js_end();
}
function js_die($text, $shut_up = true, $refresh = false)
{
    if ($shut_up) {
        global $return_id;
        if (!isset($return_id) || !$return_id)
            $return_id = "NO RETURN ID GIVEN";

        js_return($text, $return_id);

        exit;
    }
    js_alert($text);

    if ($refresh)
        js_refresh_page();

    exit;
}
function js_start()
{
    global $JS_STARTED;
    if (!isset($JS_STARTED))
        $JS_STARTED = 0;

    $JS_STARTED++;

    if ($JS_STARTED > 1)
        return;

    if (!in_js())
        echo "\n<script>\n";
}
function js_end()
{
    global $JS_STARTED;
    if (!isset($JS_STARTED) || !$JS_STARTED)
        return js_log("WARNING: Js was ended before it started...");

    $JS_STARTED--;

    if (!in_js() && !$JS_STARTED)
        echo "\n</script>\n";

}
function in_js($set = "BIBBLEDEEFARTBLOOPBALLS")
{
    if ($set !== "BIBBLEDEEFARTBLOOPBALLS")
        define('IN_JS', $set);
    return defined('IN_JS') ? IN_JS : false;
}
function js_log($text)
{
    js_start();
    if (is_array($text)) {
        foreach ($text as $key => $t) {
            if (is_numeric($key))
                js_log($t);
            else
                return js_log(smart_nl2br($text));
        }
        return;
    } elseif (strpos($text, "\n"))
        return js_log(explode("\n", $text));

    $text = addslashes($text);
    echo "\njs_log('$text');\n";
    js_end();
}
function js_alert($text)
{
    js_start();
    echo "\nalert('" . addslashes($text) . "');\n";
    js_end();
}
function json_save($decoded, $hostname = false)
{
    $template = JSON_TO_STORED;
    $results = array();
    extract_json_vars($decoded, $template, $results);
    if (!$hostname && isset($results['hostname']))
        $hostname = $results['hostname'];

    if ($hostname && $results) {
        if (isset($results['hostname'])) {
            $results['last_refresh'] = time();
            js_return(time_since($results['last_refresh']), "last_refresh");
        }
        $db_all = get_db_all();
        foreach ($results as $key => $value)
            db_set_val($hostname, $key, $value, false);

        save_db_cache();
        $count = count($results);
        $confirm_string = "Saved $count Received Variables for $hostname: ";
        //js_log($confirm_string);
        return true;
    }
}

function js_removeFromDhtmlQueue($task_id)
{
    js_start();
    echo "removeFromDhtmlQueue($task_id);\n";
    js_end();
}

function extract_json_vars(&$input, &$template, &$results)
{
    global $return_id;
    foreach ($template as $search => $place) {
        if (!isset($input[$search]))
            continue;
        if (is_array($place)) {
            if (!is_array($input[$search]))
                js_die("$search is array but js disagrees for $return_id");
            extract_json_vars($input[$search], $place, $results);
            continue;
        } else {
            //if (is_array($input[$search]))
            //    js_die("$search is NOT array but js disagrees for $return_id");
            $results[$place] = $input[$search];
        }
    }
}
function json_append($input)
{
    if (!is_array($input))
        return js_append($input);
    $template = JSON_TO_DISPLAY;
    $results = array();
    extract_json_vars($input, $template, $results);
    foreach ($results as $destination => $value)
        js_return($value, $destination);
    js_append($input);
}
function json_return($input, $display_mode = "full")
{
    if (!is_array($input))
        return js_return($input);
    $template = JSON_TO_DISPLAY;

    $results = array();
    extract_json_vars($input, $template, $results);
    foreach ($results as $destination => $value)
        js_return($value, $destination);
    if ($display_mode == "full")
        js_append($input);
    elseif ($display_mode == "short")
        js_tiny_return($input);
}
function js_tiny_return($input)
{
    $input = smart_nl2br($input, 4, 34);
    js_return($input, false, "other");

}
function js_append($text, $destination = "", $value_name = false)
{
    return js_output($text, $destination, $value_name, "js_append");
}
function js_return($text, $destination = '', $value_name = false)
{
    return js_output($text, $destination, $value_name, "js_return");
}
function js_output($text, $destination = "", $value_name = false, $mode =
    "js_return")
{
    if ($destination) {
        if (js_multi_destination($text, $destination, $mode))
            return;
    } else
        $destination = 'js_output';


    $append = "_td_$destination";


    global $return_id;
    if (!isset($return_id) && ($return_id = ""))
        js_log(($return_id = "return id not set for $mode in js_output"));
    if (!$return_id)
        js_log("return id not valid for $mode in js_output");
    $value = addslashes(format_display_value($text, $destination, $return_id, $value_name));
    if (!$return_id) {
        js_log($value);
        return false;
    }

    switch ($mode) {
        case "js_return":
            js_return_start($return_id, $append);
            echo $value;
            js_return_end();
            return;
        case "js_append":
            js_append_start($return_id, $append);
            echo $value;
            js_append_end();
            return;
        default:
            die("mode $mode not recognized in js_output");
    }
    return true;

}
function js_multi_destination($text, $destinations, $mode = "js_return")
{
    if (!$destinations)
        return false;
    $appends_string = js_format_return_id(" ", $destinations);
    $appends = explode(JAVASCRIPT_ID_DELIMITER, $appends_string);
    if (count($appends) == 1)
        return false;
    $probable_varname = false;
    foreach ($appends as $ap) {
        $ap = trim($ap);
        if (!$probable_varname)
            $probable_varname = $ap;
        switch ($mode) {
            case "js_return":
                js_return($text, $ap, $probable_varname);
                break;
            case "js_append":
                js_append($text, $ap, $probable_varname);
                break;
            default:
                die("invalid mode in js_multi_destination: $mode ");
        }

    }
    return true;

}
function js_exec_selected($array, $relay = false, $alt_command_var = false)
{

    $command_string = addslashes(construct_command_string($array));
    if ($relay)
        $js_command = (SCAN_MODE ? "exec_tasmota_relays_ip" :
            "exec_tasmota_relays_hostname");
    else
        $js_command = (SCAN_MODE ? "exec_tasmota_selected_ip" :
            "exec_tasmota_selected_hostname");

    if ($alt_command_var)
        $command_string = $alt_command_var;
    else
        $command_string = "'$command_string'";

    $js_command .= "($command_string);";
    return $js_command;
}
function js_exec_command($array, $data_id, $return_id, $alt_command_var = false)
{

    $command_string = addslashes(construct_command_string($array));
    $data_id_js = addslashes($data_id);
    $return_id_js = addslashes(js_format_return_id($return_id));


    $js_command = (SCAN_MODE ? "exec_tasmota_ip" : "exec_tasmota_hostname");

    if ($alt_command_var)
        $command_string = $alt_command_var;
    else
        $command_string = "'$command_string'";
    $js_command .= "('$data_id_js','$return_id_js',$command_string);";
    return $js_command;

}

function click_all_js($caption)
{
    $button_id_suffix = "_$caption" . "_button";
    return 'click_tasmota_command_buttons(\'' . $button_id_suffix . '\');';

}
function js_remove_hostname($hostname)
{
    $hostname = addslashes($hostname);
    js_start();
    echo "\nremove_tasmota('$hostname');\n";
    js_end();
}
function time_since($time)
{
    date_default_timezone_set("America/Phoenix");
    $last_refresh = time() - $time;
    if (!$last_refresh)
        return date("m j g:ia", $time) . " (NOW)";
    if ($last_refresh < 0) {
        $last_refresh *= -1;
        $refresh_labels = array(
            86400 => " days from now",
            3600 => " hours from now",
            60 => " minutes from now",
            1 => " seconds from now");

    } else {
        $refresh_labels = array(
            86400 => " days ago",
            3600 => " hours ago",
            60 => " minutes ago",
            1 => " seconds ago");

    }

    krsort($refresh_labels);

    foreach ($refresh_labels as $secs_to_check => $refresh_label)
        if ($last_refresh >= $secs_to_check) {
            $last_refresh = round($last_refresh / $secs_to_check, 1) . $refresh_label;
            break;
        }
    $last_refresh = date("D M j", $time) . " ($last_refresh)";
    return $last_refresh;
}
function js_pass_exec_vars()
{
    global $device_password, $device_username;
    $list_name = list_name();
    $vals = array(
        'list_name' => $list_name,
        "exfpath" => JS_PATH,
        "display_mode" => LIST_DISPLAY_MODE);
    $device_vals = array(
        "protocol" => "http",
        "username" => $device_username,
        "password" => $device_password);

    js_start();

    echo "\nvar tasmota_exec_vars = " . '{}' . ";\n";
    foreach ($vals as $key => $value) {
        $value = addslashes($value);
        echo "\ntasmota_exec_vars['$key']='$value';\n";
    }
    echo "\nvar tasmota_device_vars = " . '{}' . ";\n";
    foreach ($device_vals as $key => $value) {
        $value = addslashes($value);
        echo "\ntasmota_device_vars['$key']='$value';\n";
    }
    echo "\nvar js_master_dump_id ='" . JAVASCRIPT_DUMP_ID . "';\n";

    js_end();

}
function js_dump_line($input)
{
    if (!is_array($input))
        $input = array($input);
    js_start();
    echo "\n";
    echo implode("\n", $input);
    echo "\n";
    js_end();
}
function js_master_dump($input)
{
    $text = addslashes(smart_nl2br($input));
    $js = "master_dump_append('$text');";
    js_dump_line($js);
}
?>