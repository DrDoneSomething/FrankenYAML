<?php
defined("IN_YAML_HELPER") || die("not in helper");
function hijack_start_page()
{
    echo "<table class=\"multi_col_config\">";
    echo '<tr><th class="config_name" colspan="' . 1 . '">LISTS</th></tr>';
    foreach (LIST_DB_KEYS as $key => $label) {
        $url = $_SERVER['REQUEST_URI'] . "&list_name=$key";
        echo '<tr><td><a href="' . $url . '">' . $label . '</a>';
        if ($key != TASMOTA_IP_SCAN_LIST)
            echo ' [<a href="' . $url . '&display_mode=short">short</a>]';
        echo '</td></tr>';
    }
    echo '</table>';
    display_bottom_table();
    return "return";
}
function display_bottom_table()
{
    form_close();
    echo '<table><tr><td valign="top">';
    display_add_tasmota_box();

    echo '</td><td width="30">&nbsp;</td><td valign="top">';

    display_tasmota_login();

    echo "</td></tr></table>";
}

function display_tasmota_login()
{
    global $device_password, $device_username;
    if (defined("DISPLAY_TASMOTA_LOGIN_RUN"))
        die("You can only display login once per page due to javascript stuff. This is a coding error. My B");
    define("DISPLAY_TASMOTA_LOGIN_RUN", true);

    echo "<br /><table class=\"multi_col_config\">";
    echo '<tr><th class="config_name" colspan="' . 2 .
        '">Tasmota Login Info</th></tr>';
    echo '<tr><td>Login</td><td>';
    itext("tasmota_login_js_only", $device_username, "", "Login for Tasmota devices",
        "tasmota_js_login");
    echo '</td></tr>';

    echo '<tr><td>Password</td><td>';
    ipassword("tasmota_password_js_only", $device_password, "tasmota_js_password", false);
    echo '</td></tr>';

    echo '<tr><td>';
    $js_url = js_pass_url();
    $js_url .= "tasmota_login='+document.getElementById('tasmota_js_login').value+'";
    $js_url .= "&tasmota_password='+document.getElementById('tasmota_js_password').value+'";
    
    $tooltip = ' title="Set Cookie" ';
    $caption = "Set Login/cookie";
    $button_id = "set_tasmota_login_button";

    echo '<button ' . $tooltip . ' class="button_list" id="' . $button_id .
        '" type="button" onclick="dhtmlLoadScriptAddToQueue(\'' . $js_url . '\');">' . $caption .
        '</button>';

    echo '</td><td>';
    $js_url .= "&clear_tasmota_login=1";
    $tooltip = ' title="Clear Cookie" ';
    $caption = "Clear Login/cookie";
    $button_id = "clear_tasmota_login_button";
    $more_onclick = "document.getElementById('tasmota_js_login').value = ''; ";
    $more_onclick .= "document.getElementById('tasmota_js_password').value=''; ";
    echo '<button ' . $tooltip . ' class="button_list" id="' . $button_id .
        '" type="button" onclick="' . $more_onclick . ' dhtmlLoadScriptAddToQueue(\'' .
        $js_url . '\');">' . $caption . '</button>';

    echo '</td></tr>';
    echo '</table>';

}
function display_add_tasmota_box()
{

    $start_scan = gv_or_else("start_scan", "0");
    $end_scan = gv_or_else("end_scan", "254");
    $ip_prefix = gv_or_else("ip_prefix", "192.168.1.");
    $justone_ip = gv_or_blank("single_ip");
    $gets = $_GET;
    unset($gets['single_ip']);
    unset($gets['list_name']);
    unset($gets['ip_prefix']);
    unset($gets['start_scan']);
    unset($gets['end_scan']);
    unset($gets['scan_ips']);

    form_close();
    echo '<table class="multi_col_config">';
    echo '<tr><th class="config_name" colspan="2">Add Tasmota</th></tr>';
    echo '<tr><td valign="top">';
    $button_attributes = array(
        "caption" => "Scan IP Range",
        "type" => "submit",
        "form" => "scan_ip_form",
        "name" => "list_name",
        "js_command" => "",
        "id" => "scan_ip_form_button",
        "value" => TASMOTA_IP_SCAN_LIST);

    form_open(array(
        "method" => "get",
        "action" => "./",
        "id" => "scan_ip_form"));
    ihide_these_vars($gets);
    echo '<table class="multi_col_config">';
    echo '<tr><th>Scan Range</th></tr>';
    echo '<tr><td>';
    itext("ip_prefix", $ip_prefix, "IP Prefix");
    echo '</td></tr><tr><td>';
    itext("start_scan", $start_scan, "Start");
    echo '</td></tr><tr><td>';
    itext("end_scan", $end_scan, "End");
    echo '</td></tr><tr><td>';
    ihide("scan_ips", 1);
    echo make_button($button_attributes);
    echo '</td></tr>';
    echo '</table>';
    form_close();

    echo '</td><td valign="top">';

    form_open(array(
        "method" => "get",
        "action" => "./",
        "id" => "single_ip_form"));

    ihide_these_vars($gets);
    echo '<table class="multi_col_config">';
    echo '<tr><th>Enter Hostname or IP</th></tr>';
    echo '<tr><td>';
    itext("single_ip", $justone_ip, " ", "ex: 192.168.1.52", "single_ip_textbox");

    $button_attributes = array(
        "caption" => "Go",
        "type" => "submit",
        "form" => "single_ip_form",
        "name" => "list_name",
        "js_command" => "",
        "id" => "single_ip_form_button",
        "value" => TASMOTA_IP_SCAN_LIST);

    echo make_button($button_attributes);
    echo '</td></tr>';
    echo '</table>';

    echo '</td></tr></table>';
    echo '</form>';

}

?>