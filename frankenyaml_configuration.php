<?php

/**
 * @author Dr Done something
 * @copyright 2020
 * These are some required parts, mostly for parsing but some odds and ends here as well.
 */

defined("IN_YAML_HELPER") || die("not in helper");
 // build_dir is where your files are placed when it builds them
define("BUILD_DIR","saved_data");
define("HELP_DIR", "helps");
define("SAVE_IN_INTEGRATION_PATH","[SAVE_WITHIN_INTEGRATION]");
define("HELP_FILE_PLACEHOLDER_TEXT","#*# INSERT HELP TEXT HERE*");
define("COOKIE_NAME","FrankenYAML");
define("COOKIE_KEYS",array('uid','user','passwordmd5','tasmota_login','tasmota_password'));
define("SAVE_FILENAME","entities.txt");
define("BUILD_ALL_FILES_DIR","all_files");
define("IDLE_ACCOUNT_DELETE_DAYS",90);
define("INITIALIZATION_VECTOR",'w????Nex???}??f');
define("EXTENSIONS_DIRECTORY","extensions");
// In the name of all that is holy DO NOT CHANGE THIS
define("JAVASCRIPT_ID_DELIMITER","[AND]");


// the program removes old files from the build_dir after
// a time, this does not apply to accounts
$prune_older_than_minutes = 5;

$disabled_item_prefix = "#<DISABLED>||";


$list_name_concat = "__";
//user modifiable:
$add_recommended_integrations = true;
$retain_yaml_comments = true;

// Directory to place all integrations
$integrations_location = "integrations";

//relative to INTEGRATIONS
$entities_location = "entities";


// the following is a list of all possible integrations 
// if you get a "Cannot find type for integration " parse error, just add it to the list
// Make sure you add it to the right one though!

// Settings 
// these are a list of settings 
// They are barely changed at all but include paths are touched up
// if you want to have them broken off more, use the nest lists

$settings_integrations = array(
    'ios',
    'logger',
    'mqtt',
    'default_config',
    'conversation',
    'downloader',
    'tts',
    'configuration',
    'discovery',
    'cloud',
    'config',
    'frontend',
    'hacs',
    'hassio',
    'history',
    'homeassistant',
    'homekit',
    'http',
    'influxdb',
    'ecovacs',
    'logbook',
    'lovelace',
    'map',
    'mobile_app',
    'recorder',
    'speedtestdotnet',
    'ssdp',
    'stream',
    'sun',
    'system_health',
    'telegram_bot_conversation',
    'toon',
    'updater',
    'verisure',
    'alarm_control_panel',
    'yeelight',
    'zeroconf',
    'zwave',
    'python_script');
    
// The "key" is the location of where to look for the files
// default is $entities_location/[integration name]
// 
    
$dict_integrations = array(
    'input_boolean',
    'input_datetime',
    'input_number',
    'input_select',
    'input_text',
    'groups'=>'group',
    'panel_iframe',
    'scripts'=>'script',
    'shell_command');
    
// The "key" is the location of where to look for the files
// default is $entities_location/[integration name]
// 
$list_integrations = array("$entities_location/switches"=>"switch",
'automations'=>'automation',
'scenes'=>'scene old',
'scene.yaml'=>'scene',
'sensor',
'binary_sensor',
'media_player');

$nested_configuration = array("homeassistant/packages");

// nested_list and nested_dict - these are used to create files from within integrations
// settings seem to break apart real well, such as homeassistant -> customize
// however, it does not seem like you can nest within entities with HA yet

// The "key" is the location of where to look for the files

// NOTE FOR NESTED_LIST: !! You must do a list_name_scheme! eg: 
$nested_list = array("ios/push/categories",'influxdb'=>'influxdb/include/entities');

$nested_dict = array(
    'customizations/entities' => 'homeassistant/customize',
    'customizations/globs' => 'homeassistant/customize_domain',
    'customizations/domains' => 'homeassistant/customize_glob',
    'themes'=>'frontend/themes');
    
    
// FOR LISTS!: use 'integration_name' to make it specific for an integration!
// eg: 'integration_name:automation' => 'alias'
// each line within the entity is a "field", you can combine fields with "[and]"
// 
$list_name_scheme = array(
    'platform:mqtt' => 'name',
    'integration_name:ios/push/categories'=>'name',
    'integration_name:automation' => 'alias',
    'integration_name:scene' => 'id',
    'platform:broadlink' => 'platform[AND]host',
    'platform:onkyo' => 'name');
    
// FOR THE LOVE OF ALL THAT IS HOLY DO NOT ADD ANYTHIGN TO THIS ARRAY BY DEFAULT!

$disabled_item_list = array(array("type"=>"settings_integrations","value"=>"weblink","key"=>"","warning"=>"DEPRECATED IN HA 0.107.0"),
array("type"=>"recommended_integrations","value"=>"array_settings_integrations","key"=>"","warning"=>"Inserting all the settings is a really bad idea, it will enable every settings integration in homeassistant. Your poor little pi will shit a chicken"));
// recommended_integrations - dump all the "integrations" or nests
// These will populate even if they do not exist -- allows for creation
// of directories not yet in use
$recommended_integrations = array('array_dict_integrations','array_list_integrations','array_nested_dict','array_nested_list');
//$dicts +$lists + $nested_dict+$nested_list;    


?>