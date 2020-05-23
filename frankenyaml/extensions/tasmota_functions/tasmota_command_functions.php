<?php
defined("IN_YAML_HELPER") || die("not in helper");




// RUN WITHIN THE LOOP!!
function construct_command_url($array)
{
    global $device_username, $device_password, $ip_address, $hostname;

    if (SCAN_MODE)
        $url = "http://$ip_address/cm?user=$device_username&password=$device_password&cmnd=";
    else
        $url = "http://$hostname/cm?user=$device_username&password=$device_password&cmnd=";

    $i = 0;
    if (is_string($array)) {
        $exploded = explode(";", $array);
        $array = array();
        foreach ($exploded as $cmnd) {
            $subsploded = explode(" ", trim($cmnd), 2);
            $cm = $subsploded[0];
            $cmval = isset($subsploded[1]) ? $subsploded[1] : false;
            if (isset($cm)) {
                echo "duplicate command $cm, skipped<br />";
                continue;

            }
            $array[$cm] = $cmval;
        }
    }
    $append_array = array();
    foreach ($array as $command => $value) {
        if (htmlentities($command) != $command) {
            echo "Error: Cannot construct command, command '$command' contains weird shit";
            return "";
        }
        if (htmlentities($value) != $value) {
            echo "Error: Cannot construct command, value '$value' contains weird shit";
            return "";
        }
        $command = trim($command);
        if (substr($command, 0, 7) == "Backlog") {
            if (!$i) {
                echo "Error: Cannot construct command, backlog too late command '$command' value '$value' ";
                return "";

            }
            $command = substr($command, -7);
            $command = trim($command);
        }
        $value = trim($value);
        if (!$i && count($array) > 1) {
            $command = "backlog $command";
        }
        if ($value !== false && $value !== "")
            $append_array[] = urlencode("$command $value");
        else
            $append_array[] = "$command";


        $i++;
    }
    return $url . implode(";", $append_array);

}

function cmnd_to_array($array)
{
    if (!is_array($array)) {

        $cmd_arr = explode(";", $array);
        $array = array();
        foreach ($cmd_arr as $this_commmand) {
            $this_commmand = trim($this_commmand);
            if (stripos($this_commmand, "backlog") === 0)
                $this_commmand = trim(substr($this_commmand, 7));
            if (strpos($this_commmand, " "))
                list($this_commmand, $tparam) = explode(" ", $this_commmand, 2);
            else
                $tparam = "";
            $array[$this_commmand] = $tparam;

        }
    }
    
    return $array;
    
}
// RUN WITHIN THE LOOP!!
function construct_command_string($array)
{
    $i = 0;
    if (!is_array($array)) {

        $array = cmnd_to_array($array);
    }
    $append_array = array();
    foreach ($array as $command => $value) {
        if (htmlentities($command) != $command) {
            js_die( "Error: Cannot construct command, command '$command' contains weird shit",false);
            return "";
        }
        if (htmlentities($value) != $value) {
            js_die( "Error: Cannot construct command, value '$value' contains weird shit",false);
            return "";
        }
        $command = trim($command);
        if (strtolower(substr($command, 0, 7)) == "backlog") {
            if (!$i) {
                js_die( "Error: Cannot construct command, backlog should be at beginning of command '$command' value '$value' ",false);
                return "";

            }
            $command = substr($command, -7);
            $command = trim($command);
        }
        $value = trim($value);
        if (!$i && count($array) > 1) {
            $command = "backlog $command";
        }
        if ($value !== false && $value !== "")
            $append_array[] = "$command $value";
        else
            $append_array[] = "$command";


        $i++;
    }
    return implode(";", $append_array);

}

?>