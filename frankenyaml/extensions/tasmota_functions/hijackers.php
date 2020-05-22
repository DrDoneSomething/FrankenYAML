<?php
defined("IN_YAML_HELPER") || die("not in helper");
function hijack_page()
{

    $database_built = build_db_constant(false);
    if (isset($_GET['tasmota_login']))
        return js_tasmota_login();

    if (!issetC("tasmota_login"))
        return hijack_tasmota_no_login();

    if (isset($_GET['popup_select']))
        return popup_select_js();

    if (isset($_GET['remove_hostname']))
        return remove_tasmota_js();

    if (isset($_GET['exec_tasmota']))
        return js_exec_tasmota_receiver();

    if (isset($_GET['cmnd_reference']))
        return js_cmnd_reference();

    if (!CALLED_FROM_YAML_HELPER)
        return "hard_redirect";

    define("IN_JS", false);

    if (!$database_built)
        return hijack_start_page();
    return "All Gravy!";
}
function hijack_tasmota_no_login()
{
    if (!CALLED_FROM_YAML_HELPER)
        return "hard_redirect";
    soft_error("Please set a login and password for your tasmota devices.");
    display_tasmota_login();
    return "return";
}


function hard_redirect()
{
    if(in_js())
    {
        js_dump_line("window.location.href= '".HOME_URL."';");
        exit;
    }
    $this_page = get_full_url("CURRENT");
    $dir_name = basename(dirname($this_page));
    list($parent, $crap) = explode($dir_name, $this_page, 2);
    $url = $parent . "?extension=Tasmota%20List";

    header("Location: $url");

    exit;
}
?>