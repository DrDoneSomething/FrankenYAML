<?php

defined("IN_YAML_HELPER") || die("not in helper");
function get_tasmota_list()
{

    $start_scan = gv_or_else("start_scan", "0");
    $end_scan = gv_or_else("end_scan", "254");
    $ip_prefix = gv_or_else("ip_prefix", "192.168.1.");
    $justone_ip = gv_or_blank("single_ip");
    $error = $other_error = "";
    while ($justone_ip) {
        $justone_ip = strtolower(trim($justone_ip));
        if (!filter_var($justone_ip, FILTER_VALIDATE_IP)) {
            $justone_ip = gethostbyname($justone_ip);
            if (!filter_var($justone_ip, FILTER_VALIDATE_IP)) {
                $error = "IP invalid & Could not resolve host '$justone_ip'";
                break;
            }

        }

        $segs = explode(".", $justone_ip);
        if (count($segs) != 4) {
            $segs = explode(".", $justone_ip);
            $error = "IP not valid $justone_ip ... shouldve been picked up by filter_var in line 16";
            break;
        }
        $start_scan = $end_scan = array_pop($segs);
        $ip_prefix = implode(".", $segs) . ".";
        break;
    }
    $tasmota_list = array();
    if (SCAN_MODE && !$error && (gv_or_blank("scan_ips") || $justone_ip)) {
        $tasmota_list = array();
        $start_scan = trim($start_scan);
        $end_scan = trim($end_scan);
        if (!is_numeric($start_scan))
            $error .= "Start number $start_scan is not a number<br />";
        if (!is_numeric($end_scan))
            $error .= "End number $end_scan is not a number<br />";
        if (!$error && $start_scan > $end_scan)
            $error .= "End number $end_scan is less than start number $start_scan<br />";

        for ($i = $start_scan; !$error && $i <= $end_scan; $i++) {
            $ip = $ip_prefix . $i;

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $error .= "IP invalid '$ip'<br />";
                continue;
            }
            $tasmota_list[] = array(
                "hostname" => "TestTasmota$i",
                "ip" => $ip,
                'last_refresh' => time(),
                "topic" => "stat/faketasmo$i/STATUS5");
        }
        $error .= $other_error;
    }
    if ($error)
        soft_error($error, "IP Scan Error");
    if (!SCAN_MODE)
        $tasmota_list = get_saved_list();
    return $tasmota_list;
}

function remove_hostname_from_db($hostname, $save_when_done = true, $continue_if_done_already = true)
{
    if (db_get_val($hostname, false, true) === UNIVERSAL_BLANK) {
        js_log("FYI: $hostname not found in list " . TASMOTA_LIST_KEY .
            ", therefore not removed");
        return $continue_if_done_already;
    }

    return db_set_val($hostname, UNIVERSAL_BLANK, false, $save_when_done);


}
function db_refresh()
{
    return get_db_all(0, true);
}
function db_get_val($hostname, $key = false, $return_blank = false, $force_refresh = false)
{
    $db = get_db_all(0, $force_refresh);
    if (isset($db[TASMOTA_LIST_KEY]))
        $list = $db[TASMOTA_LIST_KEY];
    else
        $list = array();

    if ($key === false) {
        if (isset($list[$hostname]))
            return $list[$hostname];
    } elseif (isset($list[$hostname][$key]))
        return $list[$hostname][$key];

    if ($return_blank)
        return UNIVERSAL_BLANK;
    return false;
}
function db_set_val($hostname, $key = false, $value = false, $save_when_done = false,
    $verbose = false)
{
    get_db_all();
    $result = true;
    global $get_db_all_cache_var;
    if (!$hostname) {
        js_log("Cannot set key $key because hostname invalid");
        return false;
    }
    if (!isset($get_db_all_cache_var[TASMOTA_LIST_KEY][$hostname]))
        $get_db_all_cache_var[TASMOTA_LIST_KEY][$hostname] = array();


    if ($hostname === UNIVERSAL_BLANK && ($msg = "tasmota list CLEARED"))
        unset($get_db_all_cache_var[TASMOTA_LIST_KEY]);
    elseif ($key === false || is_array($key)) {
        $verbose = true;
        $result = false;
        $msg = "ERROR: $hostname could not be updated because key was not valid: " .
            smart_nl2br($key);
    } elseif ($key === UNIVERSAL_BLANK && ($msg = "hostname $hostname removed"))
        unset($get_db_all_cache_var[TASMOTA_LIST_KEY][$hostname]);
    elseif ($value === UNIVERSAL_BLANK && ($msg = "hostname $hostname value $key removed"))
        unset($get_db_all_cache_var[TASMOTA_LIST_KEY][$hostname][$key]);
    elseif (($msg = "hostname $hostname value $key set to " . smart_nl2br($value)))
        $get_db_all_cache_var[TASMOTA_LIST_KEY][$hostname][$key] = $value;

    db_save_message($msg);
    if ($save_when_done && $result)
        return save_db_cache($verbose);
    elseif ($verbose)
        db_save_message(false, "js_log");

    return $result;
}
function db_save_message($msg = false, $return_type = "raw")
{
    global $get_db_all_cache_var_unsaved_changes;
    if (!isset($get_db_all_cache_var_unsaved_changes))
        $get_db_all_cache_var_unsaved_changes = false;
    $sc = $get_db_all_cache_var_unsaved_changes;
    if ($msg !== false) {
        if (is_bool($sc))
            $sc = array();
        if (is_array($msg))
            $sc = array_merge($msg, $sc);
        elseif ($msg === true)
            $msg = "true";
        elseif ($msg === UNIVERSAL_BLANK)
            $sc = array();
        else
            $sc[] = $msg;
        $get_db_all_cache_var_unsaved_changes = $sc;
    }
    switch ($return_type) {
        case "log":
        case "js_log":
            return js_log($sc);
        case "string":
            return smart_nl2br($sc);
        case "raw":
            return $sc;
        case "array":
            return is_array($sc) ? $sc : array();
        default:
            js_die("cannot get save message, return type $return_type not valid");
    }
}
function save_db_cache($verbose = false)
{
    $db_all = get_db_all();
    if (isset($db_all[TASMOTA_LIST_KEY]))
        uksort($db_all[TASMOTA_LIST_KEY], "strnatcasecmp");
    else
        $db_all[TASMOTA_LIST_KEY] = array();
    $result = @file_put_contents(DB_FILENAME, serialize($db_all));
    if (!$result)
        js_die("FATAL ERROR, DB SAVE " . DB_FILENAME . "  FAILED", false);
    db_save_message("SUCCESS: Changes Saved to DB");
    if ($verbose)
        db_save_message(false, "js_log");
    db_save_message(UNIVERSAL_BLANK);
    sleep(.2);
    return get_db_all(0, true);
}
function get_saved_list($force = false)
{
    $db_all = get_db_all(0, $force);
    if (!isset($db_all[TASMOTA_LIST_KEY]))
        return array();
    return $db_all[TASMOTA_LIST_KEY];
}
function get_db_all($try = 0, $force = false)
{
    global $get_db_all_cache_var;
    if (!$force && isset($get_db_all_cache_var) && is_array($get_db_all_cache_var))
        return $get_db_all_cache_var;

    if (db_save_message())
        js_die("Could not refresh, there are unsaved changes!: \n" . db_save_message(false,
            "string"));

    $get_db_all_cache_var = array();
    db_save_message(UNIVERSAL_BLANK);

    $db_all = false;
    if (!file_exists(DB_FILENAME)) {
        js_log(DB_FILENAME . " does not exist, hopefully we can build it");
        return array();
    }
    try {
        $db_all = unserialize(file_get_contents(DB_FILENAME));
    }
    catch (exception $e) {
        $exception = $e->getMessage();
        js_die("Could not unserialize " . DB_FILENAME . $exception);
    }
    if (!is_array($db_all)) {
        if (!$try) {
            js_log("db empty, retrying in 100ms");
            sleep(.2);
            return get_db_all(1, $force);
        } else
            js_die("DB empty, 2 attempts");
    }
    $get_db_all_cache_var = $db_all;
    return $db_all;
}
function build_db_constant($throw_error = true)
{
    if (defined("LIST_NAME"))
        return true;
    $fail = false;
    while (true) {
        if (!isset($_GET['list_name'])) {
            $fail = "unset";
            break;
        }
        $list_name = $_GET['list_name'];
        $const = LIST_DB_KEYS;
        if (!isset($const[$list_name])) {
            $fail = "invalid";
            break;
        }
        break;
    }
    if (!$fail) {
        define("LIST_NAME", $list_name);
        define('SCAN_MODE', ($list_name == TASMOTA_IP_SCAN_LIST));
        
        if($display_mode = gv_or_blank("display_mode"))
        {
            if($display_mode != "full" && $display_mode != "short")
                die("display_mode must be short or full");
        }
        else
            $display_mode = SCAN_MODE&&gv_or_blank("scan_ips")?"short":"full";
        
        
        
        define("LIST_DISPLAY_MODE",$display_mode);
        return true;
    }
    if (!$throw_error)
        return false;

    switch ($fail) {
        case 'unset':
            js_die("no list name specified");
        case 'invalid':
            js_die("list name '$list_name' invalid");
        default:
            js_die("list name could not be set for some reason");

    }
    return false;
}
function list_name($throw_error = true)
{
    if (!build_db_constant($throw_error))
        return false;
    return LIST_NAME;
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



?>