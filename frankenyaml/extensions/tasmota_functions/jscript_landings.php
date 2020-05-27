<?php
defined("IN_YAML_HELPER") || die("not in helper");


function js_tasmota_login()
{
    define("IN_JS", true);
    header('Content-type: text/javascript');
    if (!isset($_GET['tasmota_login']))
        js_die("no tasmota_login found : " . $_SERVER['REQUEST_URI'], false);

    $tasmota_login = $_GET['tasmota_login'];

    if (!isset($_GET['tasmota_password']))
        js_die("no tasmota_password found : " . $_SERVER['REQUEST_URI'], false);
    $tasmota_password = $_GET['tasmota_password'];

    if (($clear_tasmota = (isset($_GET['clear_tasmota_login']) && $_GET['clear_tasmota_login'])))
        $tasmota_login = $tasmota_password = "";
    if ($tasmota_login === "")
        $tasmota_login = UNIVERSAL_BLANK;
    setC("tasmota_login", $tasmota_login);
    setC("tasmota_password", $tasmota_password);
    saveC();
    if ($clear_tasmota)
        js_alert("SUCCESS: Tasmota Login Information CLEARED");
    else
        js_alert("SUCCESS: Tasmota login Set. Note that password was saved in plain text in your cookies.");
    global $device_password, $device_username;
    $device_password=$tasmota_password;
    $device_username=$tasmota_login;
    js_pass_exec_vars();
    js_refresh_list();
    exit;
}

function popup_select_js()
{
    $max_desc = 24;
    $popup_return_id = "popup_return";
    define("IN_JS", true);
    header('Content-type: text/javascript');
    $list_name = list_name();

    if (!isset($_GET['task_id']) || !($task_id = $_GET['task_id']))
        js_die("no task id found : " . $_SERVER['REQUEST_URI']);
    js_removeFromDhtmlQueue($task_id);

    if (!isset($_GET['return_id']) || !($parent_return_id = $_GET['return_id']))
        js_die("no parent_return_id found", false);


    global $hostname;
    if (!isset($_GET['hostname']) || !($hostname = $_GET['hostname']))
        js_die("no hostname found : " . $_SERVER['REQUEST_URI']);
    global $ip_address;
    if (!isset($_GET['ip_address']) || !($ip_address = $_GET['ip_address']))
        js_die("no ip_address found : " . $_SERVER['REQUEST_URI']);

    if (!isset($_GET['popup_select']) || !($mode = $_GET['popup_select']))
        js_die("no popup_select found : " . $_SERVER['REQUEST_URI']);

    $selected_mode = ($hostname == "Selected Tasmotas");

    $result_height = "max-height:90px;height:90px";
    $big_result_height = "max-height:400px;height:400px";

    $close_popup = $selected_mode;

    $html = "";
    $buttons = array();
    $show_result = false;
    $query_all = array();
    switch ($mode) {
        case "status":
            $result_height = $big_result_height;
            $cols = 2;
            $title = "Status updates for ";
            $status = tasmota_reference("status");

            $ret = array();
            $ret['name'] = "Status";
            $ret['title'] = "Get Current Brief Status";
            $ret['description'] = "Get Various Status Updates";
            $query = "STATUS";
            $ret['query'] = array($query => "");
            $ret['options'] = array();

            foreach ($status['options'] as $num => $description) {

                $opt = array();
                $opt['name'] = "$num" . ($num == 0 ? "*" : "");

                if ($num == "no_value")
                    $num = "";
                $opt['cmnd'] = array($query => $num);
                $opt['title'] = $description;
                $ret['options'][] = $opt;

            }

            $buttons[] = $ret;


            break;
        case "poweronstate":

            $cols = 1;
            $title = "PowerOnStates for ";
            $poweronstate = tasmota_reference("poweronstate");

            $ret = array();
            $ret['name'] = "PowerOnState";
            $ret['title'] = "Get Current PowerOnState";
            $ret['description'] = "";
            $query = "PowerOnState";
            $ret['query'] = array($query => "");
            $ret['options'] = array();

            foreach ($poweronstate['options'] as $num => $description) {

                $opt = array();
                $opt['name'] = "$num" . ($num == 3 ? "*" : "");
                $opt['cmnd'] = array($query => $num);
                $opt['title'] = $description;
                $ret['options'][] = $opt;

            }

            $buttons[] = $ret;


            break;
        case "switchmode":
            if (!$selected_mode)
                $show_result = true;
            $cols = 1;
            $title = "SwitchModes for ";
            $switchmode = tasmota_reference("switchmode");
            for ($switch = 1; $switch <= MAX_SWITCHES; $switch++) {
                $ret = array();
                $query = "SwitchMode$switch";
                $ret['name'] = "$query";
                $ret['title'] = "Get Current Switchmode for switch $switch";
                $ret['description'] = "";
                $query_all[] = $query;
                $ret['query'] = array($query => "");
                $ret['options'] = array();

                foreach ($switchmode['options'] as $num => $description) {
                    $opt = array();
                    $opt['name'] = "$num" . (!$num ? "*" : "");
                    $opt['cmnd'] = array($query => $num);
                    $opt['title'] = $description;
                    $ret['options'][] = $opt;
                }

                $buttons[] = $ret;
            }

            break;

        case "setoption":
            if (!$selected_mode)
                $show_result = true;
            $cols = 2;
            $title = "SetOptions";
            $setoptions = tasmota_reference("setoption");
            foreach ($setoptions as $name => $data) {
                $query_all[] = $name;
                $ret = array();
                $ret['name'] = $name;
                $ret['description'] = substr($data['description'], 0, $max_desc);
                $ret['title'] = $data['description'];
                $ret['query'] = array("$name" => "");
                foreach ($data['options'] as $value => $value_description) {
                    $ret['options'][$value]['cmnd'] = array("$name" => $value);
                    $ret['options'][$value]['name'] = $value . ($value == $data['default'] ? "*" :
                        "");
                    $ret['options'][$value]['title'] = "$value_description";
                }
                $buttons[] = $ret;
            }
            break;
        default:
            js_die("mode $mode not valid", false);

    }
    // _td_js_output
    $html .= "<table class=\"multi_col_config\">";
    $colspan_multiplier = ($show_result ? 2 : 1);
    $result_colspan = $cols * $colspan_multiplier;
    if (!$selected_mode)
        $html .= "<tr><td style=\"$result_height\" class=\"tasmota_main_result animated_result_container start\" colspan=\"$result_colspan\" id=\"{$popup_return_id}_td_js_output\">&nbsp;</td></tr>";

    $new_query_function = "popup_func_$mode" . md5($parent_return_id);
    if ($query_all) {
        $html .= "<tr><td colspan=\"$result_colspan\">";
        $html .= make_button($new_query_function . "_button", "$new_query_function();",
            "Query All " . count($query_all) . " $title", "Query $title states for all " .
            count($query_all));
        $html .= "&nbsp;</td></tr>";
    }

    $entry_count = 0;
    $row_count = 0;
    $return_id = array($popup_return_id, $parent_return_id);
    $options_chars_br = 10;
    foreach ($buttons as $button) {
        $row_count++;
        if ($entry_count == 0)
            $html .= "<tr>";
        elseif ($entry_count % $cols == 0) {
            $html .= "</tr><tr>";
            $row_count = 1;
        }
        $entry_count++;
        $html .= "<td>";
        if ($selected_mode)
            $html .= command_button_selected($button['query'], $button['name'], $button['title'],
                $close_popup);
        else
            $html .= command_button($button['query'], $button['name'], $parent_return_id, $return_id,
                $button['title'], $close_popup);
        $options_chars_count = 0;
        $options_count = 0;
        foreach ($button['options'] as $option) {
            $options_chars_count += strlen($option['name']);
            if ($options_count && $options_chars_count > $options_chars_br) {
                $options_chars_count = 0;
                $html .= "<br />";
            }
            $options_count++;
            if ($selected_mode)
                $html .= command_button_selected($option['cmnd'], $option['name'], $option['title'],
                    $close_popup);
            else
                $html .= command_button($option['cmnd'], $option['name'], $parent_return_id, $return_id,
                    $option['title'], $close_popup);
        }
        $description = $button['description'];
        if ($description && strlen($description) < strlen($button['title']))
            $description = '<span>' . $description . ' ... <br /><span class="tooltiptext">' .
                $button['title'] . '</span></span>';
        if ($description)
            $html .= "<br />$description";
        $html .= "</td>";
        if ($show_result)
            $html .= '<td class="tasmota_main_result animated_result_container start"  style="width:200px";" id="' .
                $popup_return_id . "_td_" . $button['name'] . '">&nbsp;</td>';

    }
    if ($row_count < $cols)
        $html .= '<td colspan="' . ($cols * $colspan_multiplier - $row_count * $colspan_multiplier) .
            '">&nbsp;</td></tr>';
    else
        $html .= "</tr>";
    //echo '<tr><th class="config_name" colspan="' . (count(DISPLAY_VALUES) + 2) .'">TASMOTAS</th></tr>';


    $html .= "</table>";
    $b64text = base64_encode($html);
    $title = addslashes($title . " for ");
    $slashed_hostname = addslashes($hostname);
    js_start();
    if ($query_all) {
        echo "function $new_query_function() {\n";
        foreach ($query_all as $cmnd)
            echo "     " . js_exec_command($cmnd, $parent_return_id, $return_id) . "\n";
        echo "\n}\n";


    }
    echo "var this_popup_title = '$title$slashed_hostname';\n";
    echo "var this_hostname = get_tasmota_var('{$slashed_hostname}','hostname');\n";
    echo "if(this_hostname)\n";
    echo "  this_popup_title = '$title' + this_hostname;\n";

    echo "\npopup_64(this_popup_title,'$b64text');\n";
    js_end();
    exit;
}
function remove_tasmota_js()
{
    define("IN_JS", true);
    header('Content-type: text/javascript');
    $list_name = list_name();

    if (SCAN_MODE)
        js_die("This cannot be run in IP scan mode");


    if (!isset($_GET['task_id']) || !($task_id = $_GET['task_id']))
        js_die("no task id found : " . $_SERVER['REQUEST_URI']);
    js_removeFromDhtmlQueue($task_id);

    if (!isset($_GET['return_id']) || !($parent_return_id = $_GET['return_id']))
        js_die("no return_id found", false);

    global $hostname;
    if (!isset($_GET['remove_hostname']) || !($hostname = $_GET['remove_hostname']))
        js_die("no remove_hostname found : " . $_SERVER['REQUEST_URI']);

    $result = remove_hostname_from_db($hostname, true, false);
    $log = "Remove $hostname from " . TASMOTA_LIST_KEY;
    if ($result) {
        js_log("SUCCESSFULLY $log");
        js_remove_hostname($parent_return_id);
    } else {
        js_log("FAILED to $log");
    }
    exit;
}
function js_cmnd_receive_reference()
{
    define("IN_JS", true);
    header('Content-type: text/javascript');

    if (!isset($_GET['task_id']) || !($task_id = $_GET['task_id']))
        js_die("no task id found : " . $_SERVER['REQUEST_URI']);
    js_removeFromDhtmlQueue($task_id);

    if (!isset($_GET['return_id']) || !($return_id = $_GET['return_id']))
        js_die("no return_id found", false);

    if (!isset($_GET['cmnd_reference']) || !($command_string = $_GET['cmnd_reference']))
        js_die("no cmnd_reference found", false);
    
    $is_relay = isset($_GET['is_relay']);
        
    $array = cmnd_to_array($command_string);
    $fixed_string = construct_command_string($array);
    
    if($is_relay)
    {
        $new_array = array();
        foreach($array as $cmnd => $param)
        {
            
            $cmnd = preg_replace('/[0-9]+/', '', $cmnd);
            $cmnd .= RELAY_PLACEHOLDER;
            $new_array[$cmnd]=$param;
        }
        $array = $new_array;
        $ret = array('button'=>construct_command_string($array),'textbox'=>$fixed_string);
        js_cmnd_reference("separate_insert", $return_id, $ret);
    }
    elseif ($fixed_string != $command_string)
        js_cmnd_reference("insert", $return_id, $fixed_string);

    $full = $tooltip = "";
    $results = array();
    foreach ($array as $cmnd => $param) {

        $parsed = array();
        if ($param!=="")
            $cmnd = "$cmnd $param";
        $tooltip = tasmota_has_reference($cmnd, "tooltip");
        if ($tooltip) {
            $parsed = tasmota_has_reference($cmnd, "parsed");
        }
        $ret = array('tooltip' => $tooltip, 'parsed' => $parsed);
        $results[] = $ret;
    }
    if (count($results) > 1) {
        $tooltip = "Multiple:<br />" . $fixed_string;
        $ret = array('tooltip' => $tooltip, 'array' => $results);
    }
    js_cmnd_reference("receive_reference", $return_id, $ret);


    exit;
}

function js_exec_tasmota_receiver()
{

    define("IN_JS", true);
    header('Content-type: text/javascript');
    $list_name = list_name();
    global $return_id;

    if (!isset($_GET['exec_tasmota']) || !($tasmota_64_url = $_GET['exec_tasmota']))
        js_die("no exec_tasmota found", false);

    if (!isset($_GET['return_id']) || !($return_id = $_GET['return_id']))
        js_die("no return id found", false);

    if (!isset($_GET['task_id']) || !($task_id = $_GET['task_id']))
        js_die("no task id found : " . $_SERVER['REQUEST_URI']);
    global $hostname;
    if (!isset($_GET['hostname']) || !($hostname = $_GET['hostname']))
        js_die("no hostname found : " . $_SERVER['REQUEST_URI']);

    if (!isset($_GET['display_mode']) || !($display_mode = $_GET['display_mode']))
        js_die("no display_mode found : " . $_SERVER['REQUEST_URI']);

    $exception = "";

    $url = @base64_decode($tasmota_64_url);
    if (!$url)
        js_die('url decode failed for ' . $tasmota_64_url);

    $fail = false;
    try {
        $result = @file_get_contents($url);
    }
    catch (exception $e) {
        $exception = $e->getMessage();
    }
    if ($result) {
        list($url_file, $qstring) = explode("?", $url);
        $gets = array();
        parse_str($qstring, $gets);
        if (!isset($gets['cmnd']))
            js_die("okay wanted to parse cmnd from url but not found...:<br />$url");

        $cmnd = construct_command_string($gets['cmnd']);
        $parse_url = parse_url($url);
        $ip_address = $parse_url['host'];


        try {
            $decoded = json_decode($result, true);
        }
        catch (exception $e) {
            $exception = $e->getMessage();
        }
        if ($decoded) {

            json_return($decoded, $display_mode);

            if (SCAN_MODE)
                json_save($decoded);
            else
                json_save($decoded, $hostname);

            if ($display_mode == "full")
                js_append($cmnd);
            if ($display_mode == "short")
                js_master_dump($decoded);
            js_cmnd_reference("insert", $return_id, $cmnd);
            //js_log("cmnd $cmnd resulted for $hostname");
        } else
            js_return("Could Not Parse $exception :" . $result);

        js_removeFromDhtmlQueue($task_id);

    } else
        $fail = true;


    if ($fail) {
        if(SCAN_MODE)
        {
            $ip = db_get_val($hostname,"ip_address");
            
        }
        if (isset($_GET["attempt_number"])) {
            $return = 'FAILED TO CONNECT: ATTEMPT # ' . $_GET["attempt_number"] . "<br />$url<br />Exception: $exception";
        } else
            $return = 'FAILED TO CONNECT: ATTEMPT # ?' . "<br />$url<br /> Exception: $exception";
        if ($display_mode == "short")
            js_tiny_return($return);
        else
            js_return($return);
    }


    //die("ri $return_id url : $url");
    exit;
}
?>