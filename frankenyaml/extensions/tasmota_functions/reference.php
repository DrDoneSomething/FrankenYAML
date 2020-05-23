<?php

defined("IN_YAML_HELPER") || die("not in helper");

function append_references_to_display(&$display_JSON)
{
    $setoptions = tasmota_reference("setoption");
    foreach ($setoptions as $setoption => $data) {

        $id = $setoption;
        if (isset($display_JSON[$setoption]))
            $id = js_format_return_id(array($id, $display_JSON[$setoption]));

        $display_JSON[$setoption] = $id;
    }
}
function tasmota_has_reference($cmnd, $return_type = "full", $alt_value = false)
{
    $value = $value_array = false;
    if (is_array($alt_value)) {
        $value_array = $alt_value;
        $value = false;
    } elseif ($alt_value !== false)
        $cmnd = "$cmnd $alt_value";

    if (is_array($cmnd)) {
        if (count($cmnd) > 1)
            return false;
        $i = 0;
        foreach ($cmnd as $actual_command => $value)
            break;
        $cmnd = $actual_command;
    } else {
        if (strpos("$cmnd", ";") !== false)
            return false;
        if (stripos("$cmnd", "backlog") !== false)
            return false;
        $split = explode(" ", $cmnd, 2);
        $cmnd = $split[0];
        if (count($split) == 2)
            $value = trim($split[1]);
    }

    $cmnd = trim($cmnd);
    if (!$cmnd)
        return false;

    if (is_string($value))
        $value = trim(strtolower($value));
    if ($value === "")
        $value = false;
    if (strtoupper($value) == "ON")
        $value = 1;
    if (strtoupper($value) == "OFF")
        $value = 0;

    $with_scrap_number = array(
        "switchmode",
        "power",
        "pulsetime");
    $with_value = array(
        "poweronstate",
        "ap",
        "powerretain",
        "switchretain",
        "buttonretain",
        "status",
        "mqttretry");
    $without_value = array(
        "mqtthost",
        "mqttuser",
        "mqttpassword");
    $withnumber = array("setoption","ping","ipaddress","password");

    $without_value = array_fill_keys($without_value, "without_value");
    $with_scrap_number = array_fill_keys($with_scrap_number, "scrap_number");
    $withnumber = array_fill_keys($withnumber, "withnumber");
    $with_value = array_fill_keys($with_value, "with_value");
    $search = array_merge($with_scrap_number, $withnumber, $with_value, $without_value);

    $found = false;
    foreach ($search as $var => $mode) {
        if ($mode == "scrap_number" || $mode == "withnumber")
            $search_command = preg_replace('/[0-9]+/', '', $cmnd);
        else
            $search_command = $cmnd;
        if (strtolower($search_command) == $var) {

            $found = $var;
            break;
        }
    }
    if (!$found)
        return false;
    if ($return_type == "bool")
        return $found;
    $ret = array(
        "name" => $found,
        "set" => $value,
        "value_desc" => $value,
        "value_error" => false,
        "general_error" => false,
        "desc" => "",
        "desc_short" => "");
    $error_text = "";
    $value_error_text = "";
    switch ($mode) {
        case "without_value":
            $ref = tasmota_reference($found);
            $ret['desc'] = $ref['description'];
            $ret['desc_short'] = substr($ret['desc'], 0, 24) . "...";
            $ret['value_desc'] = "";
            break;
        case "withnumber":
            // mainly for setoption...
            $ref = tasmota_reference($found);
            $option_found = false;
            $gen_data = current($ref);
            foreach ($ref as $option => $data) {
                if (strtolower($option) == strtolower($cmnd)) {
                    $option_found = true;
                    break;
                }
            }
            if (!$option_found) {
                $ret['desc'] = $gen_data['description'];
                $ret['desc_short'] = substr($ret['desc'], 0, 24) . "...";
                $ret['value_desc'] = $ret['value_error'] = "$cmnd option not found!";
                break;
            }

            $ret['desc'] = $data['description'];
            $ret['desc_short'] = substr($ret['desc'], 0, 24) . "...";

            $ret['name'] = $option;
            if ($value !== false) {
                $ret['set'] = "<b>$value</b>";
                if(!$data['options'])
                {
                    $ret['value_desc'] ="";
                    break;
                }
                if (isset($data['options'][$value])) {
                    $ret['value_desc'] = $data['options'][$value];
                    break;
                }
                $ret['value_error'] = $ret['value_desc'] = "$cmnd VALUE $value is NOT valid!";
                break;
            }


            break;
        case "scrap_number":
            $cmnd = preg_replace('/[0-9]+/', '', $cmnd);
        case "with_value":
            $ref = tasmota_reference($found);
            $ret['desc'] = $ref['description'];
            $ret['desc_short'] = substr($ret['desc'], 0, 24);
            if (strlen($ret['desc_short']) < strlen($ret['desc']))
                $ret['desc_short'] .= "...";
            if ($value === false && isset($ref['no_value']))
                $value = "no_value";
            if ($value_array && $value === false)
                $value = current($value_array);
            if ($value !== false) {
                if (($calc = reference_value_calc($ref, $value, $value_array))) {
                    $ret['set'] = "<b>$value</b>";
                    $ret['value_desc'] = " ($calc)";
                    break;

                }
                $ret['set'] = "<b>$value</b>";
                if (isset($ref['options'][$value])) {
                    $ret['value_desc'] = $ref['options'][$value];
                    break;

                }
                $ret['value_desc'] = "$cmnd $value is NOT valid!";
                $ret['value_error'] = $ret['value_desc'];
            }
            break;
    }
    $html = "";
    switch ($return_type) {
        case "parsed":
            $text = "";
            if (!$ret['set'])
                $text .= "Query ";
            $text .= "<b>$cmnd</b>";
            if ($ret['set']) {
                $text .= " set to <i>" . $ret['set'] . "</i><br />";
                if ($ret['value_error'])
                    $text .= "<i><b>Warning: </b>" . $ret['value_error'] . "</i><br />";
                else
                    $text .= "<i>" . $ret['value_desc'] . "</i><br />";
            }
            $text .= "<br />" . $ret['desc'];
            $ret['text'] = $text;
            $ret['ref'] = $ref;
            $ret['found'] = $found;
            return $ret;
        case "ref":
        case "data":
            return $ref;
        case "tooltip":
            if ($ret['general_error'])
                return $ret['general_error'];
            if (!$ret['set'])
                $html .= "<b><u>Query</u></b> ";
            $html .= "<b>{$ret['name']}</b>";
            if ($ret['set'])
                $html .= " set to " . $ret['set'];
            if ($ret['value_desc'])
                $html .= "<br />-&gt;<i><u>" . $ret['value_desc'] . "</u></i>";
            $html .= "<br />" . $ret['desc'];
            return $html;
        case "full":
            if ($ret['general_error'])
                return $ret['general_error'];

            $html = "<span>";
            $max_length = 24;
            if ($ret['set']) {
                if (strlen($ret['value_desc']) > $max_length)
                    $shortval = substr($ret['value_desc'], 0, $max_length - 2) . "...";
                else
                    $shortval = $ret['value_desc'];
                $html .= $ret['set'] . ": <i>" . $shortval . "</i><br />";

                $html .= '<span class="tooltiptext">';
                $html .= "<b>{$ret['name']}</b> ";
                $html .= 'set to ' . $ret['set'];
                if ($ret['value_desc'])
                    $html .= ": <i>" . $ret['value_desc'] . '</i><br />';
                $html .= '<br />' . $ret['desc'];

            } else {
                $html .= "Query " . $ret['desc_short'] . '<span class="tooltiptext">' . $ret['desc'];

            }
            $html .= '</span>';
            return $html;
        case "found":
            return $found;
    }

}
function tasmota_reference($reference)
{
    $return = array();


    $reference = strtolower($reference);
    switch ($reference) {
        case "pulsetime":
            $return['value_calc'] = "pulsetime";
            $return['description'] =
                "Display the amount of PulseTime remaining on the corresponding" .
                " Relay&gt;x&lt;\n&gt;value&lt; Set the duration to keep Relay&gt;x&lt; ON when Power&gt;x&lt;" .
                " ON command is issued. After this amount of time, the power will be turned OFF.\n\n0 / OFF = " .
                "disable use of PulseTime for Relay&gt;x&lt;\n\n1..111 = set PulseTime for Relay&gt;x&lt; in " .
                "0.1 second increments\n\n112..64900 = set PulseTime for Relay&gt;x&lt;, offset by 100, in 1 " .
                "second increments. Add 100 to desired interval in seconds, e.g., PulseTime 113 = 13 seconds " .
                "and PulseTime 460 = 6 minutes (i.e., 360 seconds)";
            break;
        case "power":
            $options = array("Relay OFF", "Relay On");
            $options['toggle'] = "Toggles Relay";
            $return['options'] = $options;
            $return['description'] = "Controls relay state";

            break;
        case "mqttuser":
            $return['description'] = "MQTT user login Name";
            break;
        case "mqttpassword":
            $return['description'] = "MQTT server password";
            break;
        case "mqtthost":
            $return['description'] = "Address of MQTT server";
            break;
        case "mqttretry":
            $return['value_calc'] = "mqttretry";
            $return['description'] = "Seconds to retry MQTT connection after loss";
            break;
        case "status":
            $options = array(
                "show all status information (1 - 11)",
                "show device parameters information",
                "show firmware information",
                "show logging and telemetry information",
                "show memory information",
                "show network information",
                "show MQTT information",
                "show time information",
                "show connected sensor information",
                "show power thresholds (only on modules with power monitoring)",
                "same as Status 8 (retained for backwards compatibility)",
                "show information equal to TelePeriod state message",
                "in case of crash to dump the call stack saved in RT memory");
            $options['no_value'] = "show abbreviated status information";
            $return['options'] = $options;
            $return['description'] = "Various Tasmota Status Info";
            break;
        case "powerretain":
            $options = array("OFF: Disable MQTT Retain (default)",
                    "ON: Enable MQTT retain retain");
            $return['options'] = $options;
            $return['description'] = "MQTT retain switch state";
            break;
        case "switchretain":
            $options = array("disable use of MQTT retain flag (default)",
                    "enable MQTT retain flag on switch press");
            $return['options'] = $options;
            $return['description'] = "MQTT retain button state";
            break;
        case "buttonretain":
            $options = array("disable use of MQTT retain flag (default)",
                    "enable MQTT retain flag on button press");
            $return['options'] = $options;
            $return['description'] = "MQTT Power Retain Power State on status update";
            break;
        case "ap":
            $options = array(1 => "WiFi Access Point 1", 2 => "WiFi Access Point 2");
            $return['options'] = $options;
            $return['description'] = "Choose an access point";
            break;
        case "switchmode":
            $options = array(
                "toggle (default)",
                "follow (0 = off, 1 = on)",
                "inverted follow (0 = on, 1 = off)",
                "pushbutton (default 1, 0 = toggle)",
                "inverted pushbutton (default 0, 1 = toggle)",
                "pushbutton with hold (default 1, 0 = toggle, Hold = hold)",
                "inverted pushbutton with hold (default 0, 1 = toggle, hold = hold)",
                "pushbutton toggle (0 = toggle, 1 = toggle)",
                "multi change toggle (0 = toggle, 1 = toggle, 2x change = hold)",
                "multi change follow (0 = off, 1 = on, 2x change = hold)",
                "inverted multi change follow (0 = on, 1 = off, 2x change = hold)",
                "pushbutton with dimmer mode",
                "inverted pushbutton with dimmer mode",
                "pushon mode (1 = on, switch off using PulseTime)",
                "inverted pushon mode (0 = on, switch off using PulseTime)");
            $return['options'] = $options;
            $return['description'] = "Action Performed when switch state changes";
            break;
        case "poweronstate":
            $options = array(
                "OFF = keep power(s) OFF after power up",
                "ON = turn power(s) ON after power up",
                "TOGGLE = toggle power(s) from last saved state",
                "switch power(s) to their last saved state (default)",
                "turn power(s) ON and disable further power control",
                "after a PulseTime period turn power(s) ON (acts as inverted PulseTime mode)");
            $return['options'] = $options;
            $return['description'] = "State Of device when powered up";
            break;

        case "ipaddress":
            $return = array();
            $return["IPAddress"] = array(
                'description' => 'Display IP address (restart 1 after changes)',
                'options' => array(),
                'default' => "");
            $return["IPAddress1"] = array(
                'description' => 'Set IP Address',
                'options' => array(),
                'default' => "");
            $return["IPAddress2"] = array(
                'description' => 'Set Gateway IP Address',
                'options' => array(),
                'default' => "");
            $return["IPAddress3"] = array(
                'description' => 'Set Subnet Mask',
                'options' => array(),
                'default' => "");
            $return["IPAddress4"] = array(
                'description' => 'Set DNS Server IP',
                'options' => array(),
                'default' => "");

            break;
        case "password":
            $return = array();
            $return["Password1"] = array(
                'description' => 'Password for AP 1',
                'options' => array(),
                'default' => "");
            $return["Password2"] = array(
                'description' => 'Password for AP 2',
                'options' => array(),
                'default' => "");
            break;
        case "ping":
            $return = array();
            for ($i = 1; $i <= 8; $i++)
                $return["Ping$i"] = array(
                    'description' => "Ping $i IMCP packets",
                    'options' => array(),
                    'default' => "");
            break;
        case "websend":
            $return['description'] = "Send a command to Tasmota host over http. \n"
            ."If a command starts with a \ it will be used as a link.\n".
                "[-host-:-port-,-user-:-password-] -command-".
                "example 1: [ip] POWER1 ON sends http://[ip]/cm?cmnd=POWER1 ON\n".
                "example 2: WebSend [myserver.com] /fancy/data.php?log=1234 ".
                " -&gt; sends http://myserver.com/fancy/data.php?log=1234";
            break;
        case "setoption":
            $return = array(
                'SetOption0' => array(
                    'description' => 'Save power state and use after restart (=SaveState)',
                    'options' => array(
                        0 => 'disable',
                        1 => 'enable (default)',
                        ),
                    'default' => 1,
                    ),
                'SetOption1' => array(
                    'description' => 'Set button multipress mode to',
                    'options' => array(
                        0 => 'allow all button actions (default)',
                        1 => 'restrict to single, double and hold actions (i.e., disable inadvertent reset due to long press)',
                        ),
                    'default' => 0,
                    ),
                'SetOption3' => array(
                    'description' => 'MQTT',
                    'options' => array(
                        0 => 'disable MQTT',
                        1 => 'enable MQTT (default)',
                        ),
                    'default' => 1,
                    ),
                'SetOption4' => array(
                    'description' => 'Return MQTT response as',
                    'options' => array(
                        0 => 'RESULT topic (default)',
                        1 => '%COMMAND% topic',
                        ),
                    'default' => 0,
                    ),
                'SetOption8' => array(
                    'description' => 'Show temperature in',
                    'options' => array(
                        0 => 'Celsius (default)',
                        1 => 'Fahrenheit',
                        ),
                    'default' => 0,
                    ),
                'SetOption10' => array(
                    'description' => 'When the device MQTT topic changes',
                    'options' => array(
                        0 => 'remove retained message on old topic LWT (default)',
                        1 => 'send "Offline" to old topic LWT',
                        ),
                    'default' => 0,
                    ),
                'SetOption11' => array(
                    'description' => 'Swap button single and double press functionality',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption12' => array(
                    'description' => 'Configuration saving to flash option',
                    'options' => array(
                        0 => 'allow dynamic flash save slot rotation (default)',
                        1 => 'use fixed eeprom flash slot',
                        ),
                    'default' => 0,
                    ),
                'SetOption13' => array(
                    'description' => 'Allow immediate action on single button press',
                    'options' => array(
                        0 => 'single, multi-press and hold button actions (default)',
                        1 => 'only single press action for immediate response (i.e., disable multipress detection). Disable by holding for 4 x button hold time (see SetOption32).',
                        ),
                    'default' => 0,
                    ),
                'SetOption15' => array(
                    'description' => 'Set PWM control for LED lights',
                    'options' => array(
                        0 => 'basic PWM control',
                        1 => 'control with Color or Dimmer commands (default)',
                        ),
                    'default' => 1,
                    ),
                'SetOption16' => array(
                    'description' => 'Set addressable LED Clock scheme parameter',
                    'options' => array(
                        0 => 'clock-wise mode (default)',
                        1 => 'counter-clock-wise mode',
                        ),
                    'default' => 0,
                    ),
                'SetOption17' => array(
                    'description' => 'Show Color string as',
                    'options' => array(
                        0 => 'hex string (default)',
                        1 => 'comma-separated decimal string',
                        ),
                    'default' => 0,
                    ),
                'SetOption18' => array(
                    'description' => 'Set status of signal light paired with CO2 sensor The light will be green below CO2_LOW and red above CO2_HIGH (transition yellow/orange between). The default levels are: 800ppm for low and 1200ppm for high but these can be set in user_config_override.h.',
                    'options' => array(
                        0 => 'disable light (default)',
                        1 => 'enable light',
                        ),
                    'default' => 0,
                    ),
                'SetOption19' => array(
                    'description' => 'Home Assistant automatic discovery.
WARNING On version 6.4.1.x enabling may cause a watchdog reset if used on a device with a configured sensor If you enable and then disable SetOption19, doing so does not set SetOption59= 0 and does not revert to default %prefix%/%topic%/ FullTopic',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled and also sets SetOption59 1',
                        ),
                    'default' => 0,
                    ),
                'SetOption20' => array(
                    'description' => 'Update of Dimmer/Color/CT without turning power on',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption21' => array(
                    'description' => 'Energy monitoring when power is off',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption24' => array(
                    'description' => 'Set pressure units',
                    'options' => array(
                        0 => 'hPa (default)',
                        1 => 'mmHg',
                        ),
                    'default' => 0,
                    ),
                'SetOption26' => array(
                    'description' => 'Use indexes even when only one relay is present',
                    'options' => array(
                        0 => 'messages use POWER (default)',
                        1 => 'messages use POWER1',
                        ),
                    'default' => 0,
                    ),
                'SetOption28' => array(
                    'description' => 'RF received data format',
                    'options' => array(
                        0 => 'hex (default)',
                        1 => 'decimal',
                        ),
                    'default' => 0,
                    ),
                'SetOption29' => array(
                    'description' => 'IR received data format',
                    'options' => array(
                        0 => 'hex (default)',
                        1 => 'decimal',
                        ),
                    'default' => 0,
                    ),
                'SetOption30' => array(
                    'description' => 'Enforce Home Assistant auto-discovery as light',
                    'options' => array(
                        0 => 'relays are announced as a switch and PWM as a light (default)',
                        1 => 'both relays and PWM are announced as light',
                        ),
                    'default' => 0,
                    ),
                'SetOption31' => array(
                    'description' => 'Set status LED blinking during Wi-Fi and MQTT connection problems.
LedPower must be set to 0 for this feature to work',
                    'options' => array(
                        0 => 'Enabled (default)',
                        1 => 'Disabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption51' => array(
                    'description' => 'Enable GPIO9 and GPIO10 component selections in Module Configuration
?? WARNING Do not use on ESP8266 devices! ??',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption52' => array(
                    'description' => 'Control display of optional time offset from UTC in JSON payloads',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption53' => array(
                    'description' => 'Display hostname and IP address in GUI',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption54' => array(
                    'description' => 'Apply SetOption20 settings to commands from Tuya device',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption55' => array(
                    'description' => 'mDNS service',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption56' => array(
                    'description' => 'Wi-Fi network scan to select strongest signal on restart (network has to be visible)',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption57' => array(
                    'description' => 'Wi-Fi network re-scan every 44 minutes with alternate to +10dB stronger signal if detected (only visible networks)',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption58' => array(
                    'description' => 'IR Raw data in JSON payload',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption59' => array(
                    'description' => 'Send tele/%topic%/STATE in addition to stat/%topic%/RESULT for commands: State, Power and any command causing a light to be turned on.',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption60' => array(
                    'description' => 'Set sleep mode',
                    'options' => array(
                        0 => 'dynamic sleep (default)',
                        1 => 'normal sleep',
                        ),
                    'default' => 0,
                    ),
                'SetOption61' => array(
                    'description' => 'Force local operation when ButtonTopic or SwitchTopic is set.',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'enable',
                        ),
                    'default' => 0,
                    ),
                'SetOption62' => array(
                    'description' => 'Set retain on Button or Switch hold messages',
                    'options' => array(
                        0 => 'disable (default)',
                        1 => 'don\'t use retain flag on HOLD messages',
                        ),
                    'default' => 0,
                    ),
                'SetOption63' => array(
                    'description' => 'Set relay state feedback scan at restart (#5594, #5663)',
                    'options' => array(
                        0 => 'Scan power state at restart (default)',
                        1 => 'Disable power state scanning at restart',
                        ),
                    'default' => 0,
                    ),
                'SetOption64' => array(
                    'description' => 'Switch between - or _ as sensor name separator Affects DS18X20, DHT, BMP and SHT3X sensor names in tele messages',
                    'options' => array(
                        0 => 'sensor name index separator is - (hyphen) (default)',
                        1 => 'sensor name index separator is _ (underscore)',
                        ),
                    'default' => 0,
                    ),
                'SetOption65' => array(
                    'description' => 'Device recovery using fast power cycle detection',
                    'options' => array(
                        0 => 'enabled (default)',
                        1 => 'disabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption66' => array(
                    'description' => 'Set publishing TuyaReceived to MQTT',
                    'options' => array(
                        0 => 'disable publishing TuyaReceived over MQTT (default)',
                        1 => 'enable publishing TuyaReceived over MQTT',
                        ),
                    'default' => 0,
                    ),
                'SetOption67' => array(
                    'description' => 'iFan03 Buzzer control',
                    'options' => array(
                        0 => 'disable Sonoff iFan03 buzzer (default)',
                        1 => 'enable Sonoff iFan03 buzzer',
                        ),
                    'default' => 0,
                    ),
                'SetOption68' => array(
                    'description' => 'Multi-channel PWM instead of a single light Color still works to set all channels at once.
Requires restart after change',
                    'options' => array(
                        0 => 'Treat PWM as a single light (default)',
                        1 => 'Treat PWM as separate channels. In this mode, use Power to turn lights on and off, and Channel to change the value of each channel.',
                        ),
                    'default' => 0,
                    ),
                'SetOption69' => array(
                    'description' => 'Deprecated in favor of DimmerRange
By default Tuya dimmers won\'t dim below 10% because some don\'t function very well that way.',
                    'options' => array(
                        0 => 'disable Tuya dimmer 10% lower limit',
                        1 => 'enable Tuya dimmer 10% lower limit (default)',
                        ),
                    'default' => 1,
                    ),
                'SetOption71' => array(
                    'description' => 'Set DDS238 Modbus register for active energy',
                    'options' => array(
                        0 => 'set primary register (default)',
                        1 => 'set alternate register',
                        ),
                    'default' => 0,
                    ),
                'SetOption72' => array(
                    'description' => 'Set reference used for total energy',
                    'options' => array(
                        0 => 'use firmware counter (default)',
                        1 => 'use energy monitor (e.g., PZEM-0xx, SDM120, SDM630, DDS238, DDSU666) hardware counter',
                        ),
                    'default' => 0,
                    ),
                'SetOption73' => array(
                    'description' => 'Deprecated in version 7.1.2.4 in favor of CORS command
Set HTTP Cross-Origin Resource Sharing (CORS)',
                    'options' => array(
                        0 => 'disable CORS (default)',
                        1 => 'enable CORS',
                        ),
                    'default' => 0,
                    ),
                'SetOption74' => array(
                    'description' => 'Enable internal pullup for single DS18x20 sensor',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'internal pullup enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption75' => array(
                    'description' => 'Set grouptopic behaviour (#6779)',
                    'options' => array(
                        0 => 'GroupTopic using FullTopic replacing %topic% (default)',
                        1 => 'GroupTopic is cmnd/%grouptopic%/',
                        ),
                    'default' => 0,
                    ),
                'SetOption76' => array(
                    'description' => 'Bootcount incrementing when DeepSleep is enabled (#6930)',
                    'options' => array(
                        0 => 'disable bootcount incrementing (default)',
                        1 => 'enable bootcount incrementing',
                        ),
                    'default' => 0,
                    ),
                'SetOption77' => array(
                    'description' => 'Do not power off if a slider is moved to far left',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption78' => array(
                    'description' => 'Version check on Tasmota upgrade',
                    'options' => array(
                        0 => 'enabled (default)',
                        1 => 'disabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption79' => array(
                    'description' => 'Reset counters at TelePeriod time',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption80' => array(
                    'description' => 'Blinds and shutters support',
                    'options' => array(
                        0 => 'disable blinds and shutters support (default)',
                        1 => 'enable blinds and shutters support',
                        ),
                    'default' => 0,
                    ),
                'SetOption81' => array(
                    'description' => 'Set PCF8574 component behavior for all ports',
                    'options' => array(
                        0 => 'set as regular state (default)',
                        1 => 'set as inverted state',
                        ),
                    'default' => 0,
                    ),
                'SetOption82' => array(
                    'description' => 'Reduce the CT range from 153..500 to 200.380 to accomodate with Alexa range',
                    'options' => array(
                        0 => 'CT ranges from 153 to 500 (default)',
                        1 => 'CT ranges from 200 to 380 (although you can still set in from 153 to 500)',
                        ),
                    'default' => 0,
                    ),
                'SetOption83' => array(
                    'description' => 'Uses Zigbee device friendly name instead of 16 bits short addresses as JSON key when reporting values and commands See ZbName ,',
                    'options' => array(
                        0 => 'JSON key as short address',
                        1 => 'JSON key as friendly name (default)',
                        ),
                    'default' => 1,
                    ),
                'SetOption84' => array(
                    'description' => '(Experimental) When using AWS IoT, sends a device shadow update (alternative to retained) Note: if the Topic contains \'/\' they are replaced with \'_\'',
                    'options' => array(
                        0 => 'don\'t update device shadow (default)',
                        1 => 'update device shadow',
                        ),
                    'default' => 0,
                    ),
                'SetOption85' => array(
                    'description' => 'Device group support',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption86' => array(
                    'description' => 'PWM Dimmer only! Turn brightness LED\'s off 5 seconds after last change',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption87' => array(
                    'description' => 'PWM Dimmer only! Turn red LED on when powered off',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption88' => array(
                    'description' => 'PWM Dimmer only! Buttons control remote devices',
                    'options' => array(
                        0 => 'disabled (default)',
                        1 => 'enabled',
                        ),
                    'default' => 0,
                    ),
                'SetOption89' => array(
                    'description' => 'Configure MQTT topic for Zigbee devices (also see SensorRetain) Example: tele/Zigbee/5ADF/SENSOR = {"ZbReceived":{"0x5ADF":{"Dimmer":254,"Endpoint":1,"LinkQuality":70}}}',
                    'options' => array(
                        0 => 'single tele/%topic%/SENSOR topic (default)',
                        1 => 'unique device topic based on Zigbee device ShortAddr',
                        ),
                    'default' => 0,
                    ),
                'SetOption90' => array(
                    'description' => 'Disable sending MQTT with non-JSON messages',
                    'options' => array(
                        0 => 'send all MQTT (default)',
                        1 => 'send only MQTT messages with JSON payloads',
                        ),
                    'default' => 0,
                    ),
                'SetOption91' => array(
                    'description' => 'Enable Fade at boot and power on. By default fading is not enabled at boot because of stuttering caused by wi-fi connection',
                    'options' => array(
                        0 => 'don\'t Fade at startup (default)',
                        1 => 'Fade at startup',
                        ),
                    'default' => 0,
                    ),
                'SetOption92' => array(
                    'description' => 'Alternative to Module 38: for Cold/Warm white bulbs, enable the second PWM as CT (Color Temp) instead of Warm White, as required for Philips-Xiaomi bulbs. See PWM CT in Lights',
                    'options' => array(
                        0 => 'normal Cold/Warm PWM (default)',
                        1 => 'Brightness/CT PWM',
                        ),
                    'default' => 0,
                    ),
                );
            break;
        default:
            die("reference $reference unknown");
    }
    return $return;
}
function reference_value_calc($mode, $value, $value_array = false)
{
    if (is_array($mode))
        if (isset($mode['value_calc']))
            $mode = $mode['value_calc'];
        else
            return false;
    switch ($mode) {
        case "pulsetime":
            return pulsetime_calc($value_array ? $value_array : $value);
        case "mqttretry":
            return "$value seconds";
        default:
            js_die("invalid mode $mode in reference_value_calc");
    }
}
function pulsetime_calc($i)
{
    if (is_array($i) && isset($i["Set"]))
        $i = $i['Set'];
    if (!is_numeric($i))
        return "$i is INVALID";
    if (!$i)
        return "Pulsetime Disabled";
    if ($i <= 111) {
        $ms = $i * 0.1;
        $return = "$ms Seconds";
        return $return;
    }
    $init = $i - 100;
    $hours = floor($init / 3600);
    $minutes = floor(($init / 60) % 60);
    $seconds = $init % 60;

    $return = "$hours:$minutes:$seconds";
    return $return;

}
?>