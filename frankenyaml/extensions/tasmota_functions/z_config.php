<?php
defined("IN_YAML_HELPER") || die("not in helper");
// this file needs to load AFTER everything except zz_layout
// tasmota_functions loads in alphabetical order
//therefore keep the filename as is!

// This is the RIGHT colum when viewing the page in FULL mode
// Entries will ALWAYS create a "result" table cell
// Entries will be added to the script that searches for things in the tasmota result
//   to be 
// Entries in this list will create a button IF the entry is found in
// the "reference"
// If not found in the reference, no button will be created
$display_values_vertical = array(
    "MqttHost",
    "MqttRetry",
    "SetOption19",
    "SetOption30",
    "PowerOnState",
    "PowerRetain",
    "ButtonRetain",
    "SwitchRetain",
    "last_refresh");
    
// Horizontal values to display
// you probably do not want to mess with this much
$display_values_FULL = array(
    "hostname",
    "ip_address",
    "Topic",
    "other");
$display_values_SHORT = array(
    "Result" => "js_output",
    "hostname",
    "ip_address",
    "RelayName",
    "other");

define("COMMAND_BUTTONS", array(
    "cm:STATUS 5" => "Net Status*",
    "cm:STATUS 6" => "Mqtt Status",
    "cm:mqttretry" => "MQTTRetry?",
    "cm:status 0" => "All Status**",
    "cm:setoption19" => "AutoDiscovery?",
    "cm:setoption30" => "IsLight?",
    "cm:AP 1" => "change AP 1",
    "cm:AP 2" => "AP 2",
    "cm:setoption13 1" => "Button Fix",
    "cm:setoption19 1" => "HA Auto Discovery ON",
    "cm:setoption19 0" => "HA Auto Discovery ON",
    "cm:setoption30 1" => "Make into HA light",
    "cm:mqttretry 10" => "MQTTRetry10",
    "cm:switchretain 0" => "Fix1",
    "cm:buttonretain 1" => "Fix2",
    "cm:buttonretain 0" => "Fix3",
    "cm:poweronstate 3" => "Fix4",
    "cm:powerretain 1" => "Fix5"));
// below is how the program interprets data retreived from tasmota
// array keys are the tasmota values, array values are where to place/store them
$shared_JSON = array(
    "Topic" => "Topic",
    "MqttRetry" => "MqttRetry",
    "StatusMQT" => array("MqttHost" => "MqttHost", "MqttUser" => "MqttUser"),
    "MqttUser" => "MqttUser",
    "MqttHost" => "MqttHost",
    "SetOption19" => "SetOption19",
    "SetOption30" => "SetOption30",
    "Status" => array(
        "Topic" => "Topic",
        "PowerOnState" => "PowerOnState",
        "PowerRetain" => "PowerRetain",
        "ButtonRetain" => "ButtonRetain",
        "SwitchRetain" => "SwitchRetain",
        "FriendlyName" => "RelayName"));

$stored_JSON = array("StatusNET" => array("Hostname" => "hostname", "IPAddress" =>
            "ip_address"));

$display_JSON = array("StatusNET" => array("Hostname" => "hostname", "IPAddress" =>
            "ip_address"));


$relay_JSON_prefix = array("POWER" => "POWER", "PulseTime" => "PulseTime");

// [num] is the placeholder for the relay number
$relay_commands = array(
    "PulseTime[num] 0" => "Clear PT",
    "POWER[num] 0" => '<img src="'.stored_file("light-off.png").'" />',
    "POWER[num] 1" => '<img src="'.stored_file("light-on.png").'" />');


?>