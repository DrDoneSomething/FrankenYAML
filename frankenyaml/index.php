<?php
define("IN_YAML_HELPER", true);

require ("frankenyaml_includes.php");
$popup_message = "";
$popup_title = "Warnings & Messages";
help_hijack();
$missing_files = $disp_entities = array();
auth();

get_display_entities();

if (pv_or_blank('download_zip')) {
    if (!$disp_entities)
        die("Cannot Build Zip, no display entities found");
    download_zip();
}

build_constants();

prune_old_files();


saveC();

if (!($mode = pv_or_blank('new_mode')))
    $mode = pv_or_else('mode', 'parse_settings');
$title = "FrankenYAML";
$post_action = basename(__file__);
if ($post_action == "index.php")
    $post_action = "?";
$extensions_list = array();
$extension_mode = "";
$show_extensions_menu = false;
$show_menu = ($mode != "parse_input");
if (isset($_GET))
    $gets = $_GET;
else
    $gets = array();
$style_sheets = array("./frankenyaml_css.css");
$js_files = array("./frankenyaml_javascript.js");
if (file_exists(EXTENSIONS_DIRECTORY)) {
    $extensions_file_list = scandir(EXTENSIONS_DIRECTORY);
    foreach ($extensions_file_list as $extension_file)
        if (substr($extension_file, 0, 1) != "." && !is_dir(EXTENSIONS_DIRECTORY . "/$extension_file") &&
            substr($extension_file, -4) == ".php")
            $extensions_list[pretty_filename($extension_file)] =
                $extension_file;
    if (isset($extensions_list[($name=gv_or_blank("extension"))])) {
        $search_name = substr($extensions_list[$name],0,-4);
        $show_extensions_menu = $show_menu = true;
        $mode = "extension";
        $title = $extension_mode = gv_or_blank("extension");
        if(file_exists(($css = EXTENSIONS_DIRECTORY."/$search_name.css")))
            $style_sheets[] = $css;
        if(file_exists(($js = EXTENSIONS_DIRECTORY."/$search_name.js")))
            $js_files[] = $js;
        //$gets['extension'] = $extension_mode;
    }
}

foreach ($gets as $key => $value)
    $post_action .= "$key=" . urlencode($value) . "&";
$post_action = substr($post_action, 0, -1);

if (!count($_POST) && !$extension_mode) {
    $show_extensions_menu = true;
    $show_menu = true;
    $popup_title = "Welcome!";
    $popup_message = rethelp_text("configure_general");
}

headsent(true);
define("MODE", $mode);

echo '<html><head><title>'.$title.'</title>';

echo_head_redirect();
jump_to_onload();
foreach($js_files as $js)
    echo '<script src="'.$js.'"></script>';
    
foreach($style_sheets as $css)
    echo '<link rel="stylesheet" type="text/css" href="'.$css.'" />';
    
echo '</head>
<body onload="frankenyaml_onload();">';

echo '<form method="post" id="form" action="' . $post_action . '">';
if ($show_menu) {
    echo '<table><tr><td width="80%">';

    if ($show_extensions_menu && $extensions_list) {
        echo "<h2>Extensions</h2>";
        $hrefs = array();
        $hrefs[] = '<a href="?">FrankenYAML</a>';
        foreach ($extensions_list as $ext_name => $exfn)
            $hrefs[] = '<a href="?extension=' . $ext_name . '">' . $ext_name . '</a>';
        echo implode(" || ", $hrefs);

        echo '</td><td width="20%">';
    }
    if(!$extension_mode)
    login_button();
    echo "</td></tr></table><hr />";

}

switch ($mode) {
    case 'parse_input':
        require ("frankenyaml_parse.php");
        break;
    case 'display':
        require ("frankenyaml_display.php");
        break;
    case 'parse_settings':

        require_once ("frankenyaml_config_page_functions.php");

        echo_saved_page();
        break;
    case 'login':
        require ("frankenyaml_login.php");

        break;
    case 'extension':
        if (!isset($extensions_list[$extension_mode]))
            die("extension file for $extension_mode not found");
        $exfpath = EXTENSIONS_DIRECTORY . "/{$extensions_list[$extension_mode]}";
        if (!file_exists($exfpath))
            die("extension file $exfpath not found");
        include ($exfpath);
        break;


}

jump_to_onload(true);
save_display_entities();
?>


</form>
<div id="popup1" class="overlay" onclick="collapse_warnings();">
	<div class="popup" onclick="event.stopPropagation();">
		<h2 id="popup1title"><?php echo $popup_title; ?></h2>
		<a class="close" id="popup_close_link" href="javascript:collapse_warnings();" >&times;</a>
		<div class="content" id="popup1message"><?php echo $popup_message; ?></div>
	</div>
</div>
</body>
</html>