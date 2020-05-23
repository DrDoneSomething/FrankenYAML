<?php


/**
 * These are various functions that make it easier for me to write HTML with php.
 * This makes it a TON easier to pass post variables since this program can function
 * entirely without storing any data in temporary storage!

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/
define("MULTIVAR_DELIMITER", "|!|");
define("MULTIVAR_EQUALS", "|=|");
define("CLEAR_USE_KEY","4321jlkBLARGInpoop");
defined("IN_YAML_HELPER") || die("not in helper");

define("RESERVED_ELEMENT_ATTRIBUTES",array("contents","innerHTML","id_suffix","tag","tag_name","id_prefix"));
$form_keys = array();
$parse_errors = array();
$parse_errors_count = 0;
$post_var_cache = array();
$html_functions_total_inputs = 0;
$html_functions_all_ids = array();
function use_key($name, $add = true)
{
    global $form_keys, $html_functions_total_inputs;
    if($name === CLEAR_USE_KEY)
    {
        $form_keys = array();
        return 0;
    }
    $html_functions_total_inputs++;
    if (isset($form_keys[$name]) && !strpos($form_keys[$name], "[]"))
        die("COULD NOT USE KEY $name -- Duplicate!");
    if ($name && $add)
        $form_keys[$name] = $name;
    return $html_functions_total_inputs;
}
function use_id($id,$add = true)
{
    $array = "Input Not Array";
    if(is_array($id))
    {
        if(!isset($id['id']))
            return true;
        $array = $id;
        $id = $id['id'];
        if($id!==0&& !$id)
            // we'll leave the error for another day
            return true;
    }
    if($add)
    {
        if(isset($html_functions_all_ids[$id]))
            die("COULD NOT USE ID $id -- Duplicate! Input: ".var_export($array,true));
        $html_functions_all_ids[$id] = $id;
        return true;
    }
    if(isset($html_functions_all_ids[$id]))
        return $id;
}
function id_attribute(&$id, $number = false)
{
    $id = $id ? $id : $number;
    $id = generate_id($id);
    use_id($id);
    return ' id="' . $id . '" ';
}
function generate_id($id = false)
{
    global $html_functions_total_inputs;
    if (!$id)
        $id = "frankenyaml_input_$html_functions_total_inputs";
    if (is_numeric($id))
        $id = "frankenyaml_input_$id";

    return $id;
}
// this is jenky as hell but it works
function input_value_handler($value)
{
    $attribute_array = array(
        "onclick=",
        "onchange=",
        "onfocus=");
    $found = false;
    foreach ($attribute_array as $search)
        if (strpos($value, $search) === 0)
            return $value;

    return ' value="' . $value . '" ';

}
function pv_or_blank($key, $default_return = "")
{
    if (isset($_POST[$key]))
        return $_POST[$key];
    global $post_var_cache;
    if (!$post_var_cache && isset($_POST['multi_var']) && ($raw_val = $_POST['multi_var'])) {
        $pvar_expl = explode(MULTIVAR_DELIMITER, $raw_val);
        foreach ($pvar_expl as $var_paring) {
            if (strpos($var_paring, "=")) {
                list($post_key, $post_val) = explode(MULTIVAR_EQUALS, $var_paring, 2);
                if (substr($post_key, -2) == "[]") {
                    $post_key = substr($post_key, 0, -2);
                    $post_val = array($post_val);
                }
                if (isset($_POST[$post_key])) {
                    parse_error("Post $post_key cannot be set to $post_val, already set to {$_POST[$post_key]}");
                    continue;
                }

                $post_var_cache[$post_key] = $post_val;
            }

        }
    }
    if (isset($post_var_cache[$key]))
        return $post_var_cache[$key];

    return $default_return;

}

function pv_match($prefix, $there_can_be_only_one = true)
{
    $ret_array = array();
    if (!isset($_POST) || !$_POST)
        return $ret_array;
    foreach ($_POST as $key => $value) {
        if (strpos($key, $prefix) === 0) {
            $key = substr($key, strlen($prefix));
            $ret_array[$key] = $key;
        }
    }
    if ($there_can_be_only_one) {
        if (count($ret_array) > 1)
            die("more than one post key found in pkey starts with $prefix: " . var_export($_POST));

        return $ret_array ? current($ret_array) : false;
    }
    return $ret_array;


}

function pv_64_import($key)
{
    $value = pv_or_blank($key);
    if (!$value)
        return array();
    return unserialize(base64_decode($value));
}
function form_open($attributes = array())
{
    global $HTML_FORM_OPEN_THING;
    if (isset($HTML_FORM_OPEN_THING) && $HTML_FORM_OPEN_THING)
        die("Form already open with attributes: " . var_export($HTML_FORM_OPEN_THING, true));
    $HTML_FORM_OPEN_THING = $attributes;
    $attrib = create_element_attributes($attributes);
    echo "<form $attrib>";
}
function form_close()
{
    global $HTML_FORM_OPEN_THING;
    $HTML_FORM_OPEN_THING = false;
    use_key(CLEAR_USE_KEY);
    echo "\n\n</form>\n\n";

}
// note:
// input must be:
// variable name => value
// takes an array full of variable names
// OR since no variables are numeric, takes an array of variables as keys with default values 
function return_attributes($input, $required_values, $optional_values = array(),$return_type="array",
    $die_on_fail = true)
{
    if (!is_array($input))
        die("return_attributes expects array for input " . var_explode($input, true));
    if (!is_array($required_values))
        die("return_attributes expects array required_values " . var_explode($required_values, true));
    if (!is_array($optional_values))
        die("return_attributes expects array for optional_values " . var_explode($optional_values, true));
    $return = array();
    $two_arrays = array($optional, $required_values);
    foreach ($two_arrays as $is_required => $array) {
        foreach ($array as $index_or_var => $var_or_val) {
            $key = is_numeric($index_or_var)?$var_or_val:$index_or_var;
            $value = is_numeric($index_or_var)?false:$index_or_var;
            
            if (is_numeric($index_or_var)) {
                $all_options[$var_or_val] = false;
                continue;
            }
            if (isset($all_options[$index_or_var]))
                die("return_attributes input structure invalid, ".
                " duplicate: key $key is already set - SENT ME BAD DATA: " . var_export($two_arrays,true));
            if(isset($input[$key]))
                $value = $input[$key];
            elseif($is_required)
            {
                if($die_on_fail)
                    die("return_attributes $key is required, not supplied ".var_export($input,true));
                return false;
            }
            $return[$key] =$value;

        }
    }
    if($return_type == "string")
        return create_element_attributes($return);
    else
        return $return;
}
function create_element_attributes($input, $array = array())
{
    if (!$input)
        $input = array();
    if (!is_array($input))
        $input = array($input);
    $array = array_merge($array, $input);
    $attrib_string = "";

    if (!$array)
        return $attrib_string;
    extract_id_from_attributes($array);
    use_id($array);
    foreach ($array as $key => $val) {
        if($val === false)
            continue;
        if(in_array($key,RESERVED_ELEMENT_ATTRIBUTES))
            continue;
        $name = is_numeric($key) ? false : strtolower($key);
        switch ($name) {
            case "style":
                if(!is_array($val))
                    die("create_element_attributes error: invalid array value for $key => ".
                    var_export($val,true)." data: ".var_export($array));
                $attrib_string .= create_attribute_style($val);
                break;
            default:
                if(is_array($val))
                    die("create_element_attributes error: invalid string/num value $key/$name => ".
                    var_export($val,true)." data: ".var_export($array));
                $attrib_string .= " $name=\"$val\" ";
                break;
            case false:
                if (!$val||!is_string($val))
                    die("create_element_attributes error: invalid string value $key => ".
                    var_export($val,true)." data: ".var_export($array));
                $attrib_string .= " $val ";
                break;

        }
    }
    return $attrib_string;

}
function extract_id_from_attributes(&$array)
{
    $id = "";
    if(isset($array['id']))
        $id = $array['id'];
    if(isset($array['id_prefix']))
        $id = $array['id_prefix'].$id;
    if(isset($array['id_suffix']))
        $id .= $array['id_prefix'];
    unset($array['id_prefix']);
    unset($array['id_suffix']);
    unset($array['id']);
    if($id!=="")
        $array['id'] = $id;
}
function create_attribute_style($array)
{
    if (!$array)
        return "";
    if (!is_array($array))
        $array = array($array);

    $return_string = ' style="';
    foreach ($array as $var => $val)
        $return_string .= "$var:$val;";
    $return_string .= '" ';
    return $return_string;
}
function ihide_64_export($name, $array)
{

    ihide($name, base64_encode(serialize($array)));

}
function match_pattern($value, $pattern)
{

    if ($pattern == $value)
        return true;
    $pattern = preg_quote($pattern, '#');

    // Asterisks are translated into zero-or-more regular expression wildcards
    // to make it convenient to check if the strings starts with the given
    // pattern such as "library/*", making any string check convenient.
    $pattern = str_replace('\*', '.*', $pattern) . '\z';

    return (bool)preg_match('#^' . $pattern . '#', $value);
}

function pv_or_else($key, $default_value)
{
    return pv_or_blank($key, $default_value);
}
function gv_or_blank($key)
{
    if (!isset($_GET[$key]))
        return "";
    return $_GET[$key];
}

function gv_or_else($key, $default_value)
{
    if (!isset($_GET[$key]))
        return $default_value;
    return $_GET[$key];
}
function itextarea($name, $contents = "", $hidden = false, $additional_attributes =
    "", $id = false)
{
    $number = use_key($name);
    $attributes = id_attribute($id, $number) . $additional_attributes;
    $html = '<textarea rows="50" cols="50" class="big" name="' . $name . '" ' . $attributes .
        '>' . $contents . '</textarea><br />';
    if ($hidden)
        hide_element($html);
    else
        echo $html; //echo '<textarea style="width:100%;height100%" rows="15" name="' . strtolower($name) .
    //   '">' . $contents . '</textarea><br />';

    return $id;
}
function hide_element($html)
{
    echo '<div style="display:none">' . $html . '</div>';
}
function itext($name, $contents = "", $label = false, $placeholder = "", $id = false)
{
    $attributes = input_value_handler($contents);
    $number = use_key($name);
    $attributes .= id_attribute($id, $number);

    $placeholder = $placeholder ? ' placeholder="' . $placeholder . '"' : $placeholder;
    if ($label === false)
        $label = $name;
    $label = $label ? "$label:<br />" : $label;
    echo $label . '<input type="text" ' . $placeholder . ' ' . $attributes .
        ' name="' . strtolower($name) . '" value="' . htmlentities($contents) .
        '" /><br />';
    return $id;
}
function ipassword($name, $contents = "", $id = false, $caption = "")
{
    $attributes = input_value_handler($contents);
    $number = use_key($name);
    $attributes .= id_attribute($id, $number);

    if ($caption === false)
        $caption = "";
    elseif ($caption)
        $caption = "$caption:<br />";
    else
        $caption = "$name:<br />";

    echo $caption . '<input ' . $attributes . ' type="password" name="' . strtolower($name) .
        '" value="' . htmlentities($contents) . '" /><br />';
    return $id;
}
function icheckbox($name, $value = 1, $checked = false, $label = false, $confirm = false,
    $id = false)
{
    $attributes = input_value_handler($value);
    $number = use_key($name);
    $attributes .= id_attribute($id, $number);
    $attributes .= ($checked ? " checked " : " ");

    $attributes .= $confirm ? ' onclick=" return confirm(\'' . format_confirm($confirm) .
        '\'); " ' : "";
    if ($label === false)
        $label = $name;
    echo '<label><input type="checkbox" name="' . strtolower($name) . '" value="' .
        $value . '" ' . $attributes . ' /> ' . "$label</label><br />";
    return $id;

}
function format_confirm($confirm)
{
    return str_replace(array(
        "\n\r",
        "\n",
        "\r",
        "'"), array(
        '\n',
        '\n',
        '\n',
        "\\'"), $confirm);
}
function imode($mode, $id = false)
{
    return ihide("mode", $mode, $id);

}
function idisp_mode($mode, $id = false)
{
    return ihide("idisp_mode", $mode, $id);

}

// values_array -> $label => $value
// mode key/value - use key/value for both
function iselect($name, $values_array, $selected_item = false, $label = false, $mode = false,
    $id = false)
{

    $number = use_key($name);
    $id_attribute = id_attribute($id, $number);
    if ($label === false)
        $label = $name;

    echo '<label>' . $label . '<select name="' . $name . '" ' . $id_attribute . '>';
    foreach ($values_array as $label => $value) {
        switch ($mode) {
            case "key":
                $value = $label;
                break;
            case "value":
                $label = $value;
                break;
        }
        $selected_item = ($selected_item === $label) ? " selected " : "";
        if (!trim($label)) {
            $label = " -- Select -- ";
        }
        echo '<option ' . $selected_item . ' value="' . $value . '">' . $label .
            '</option>';
    }
    echo '</select></label>';
    return $id;

}
function iradio_array($name, $array, $checked_value = false, $add_checkbox_name = false,
    $checkbox_label = false)
{
    $ids = array();
    foreach ($array as $value => $label) {
        $ids[$value] = iradio($name, $value, ($checked_value == $value), $label);
        if ($add_checkbox_name) {
            $checkbox_label = $checkbox_label ? $checkbox_label : $label;
            $ids[$value] = icheckbox($add_checkbox_name, $value, false, $checkbox_label, false,
                $ids[$value] . "_checkbox");
            echo '<hr width="100" align="left" />';
        }
    }
    use_key($name);
    return $ids;
}
function iradio($name, $value, $checked = false, $label = false, $id = false)
{
    $number = use_key($name, false);
    $id_attribute = id_attribute($id, $number);

    if (!$label)
        $label = $value;
    $attributes = input_value_handler($value);
    $attributes .= ($checked ? "checked" : "");

    echo '<label><input type="radio" ' . $id_attribute . $attributes . ' name="' . $name .
        '"  /> ' . "$label</label><br />";
    return $id;

}
function ihide_pv($name, $default_value = false, $id = false)
{
    $value = pv_or_else($name, $default_value);
    if ($value !== false)
        return ihide($name, $value, $id);
}
function ihide($name, $value, $id = false)
{
    $number = use_key($name);
    $id_attribute = id_attribute($id, $number);

    echo '<input type="hidden" ' . $id_attribute . ' name="' . $name . '" value="' .
        $value . '" />';
    return $id;

}
function ihide_these_vars($array)
{
    foreach($array as $key => $value)
        ihide($key,$value);
}
function ihide_vars($array,$post_vars = true)
{
    $output = array();
    foreach ($array as $key)
        $output[$key] = $post_vars?pv_or_blank($key):gv_or_blank($key);
    ihide("previous_vars", base64_encode(serialize($output)));
}
function ihide_previous()
{
    if ($raw = pv_or_blank("previous_vars")) {
        $output = @unserialize(base64_decode($raw));
        if ($output) {
            foreach ($output as $key => $value)
                ihide($key, $value);
        }
    }
}


// since all post vars are obtained through pv_or_blank, I can just encode my own arrays and send them via a submit button.

function isubmit_multi($array, $label, $confirm = false, $class = false, $id = false)
{
    $name = 'multi_var';
    $value = ret_multivar($array);

    isubmit($name, $value, $label, $confirm, $class, $id);
}
function isubmit($name, $value, $label = false, $confirm = false, $class = false,
    $id = false)
{

    $attributes = input_value_handler($value);

    $number = use_key($name, false);
    $attributes .= id_attribute($id, $number);

    //echo '<input type="submit" name="' . $name . '" value="' . $value . '" />';
    $type = "";
    if ($confirm) {
        $type = ' type="button" ';
        $type .= ' onclick=" if(confirm(\'' . format_confirm($confirm) . '\')) { this.type=\'submit\'; return true; } else { return false; } " ';
    } else {
        $type = ' type="submit" ';
    }
    $attributes .= $class ? ' class="' . $class . '" ' : "";

    if (!$label)
        $label = $name;
    echo '<button form="form" ' . $type . ' name="' . $name . '" ' . $attributes .
        '>' . $label . '</button>';
    return $id;
}
function script_button($label, $code, $confirm = false, $class = false, $id = false)
{

    $number = use_key(md5($label . $code . rand(0, 999999)), false);
    $id_attribute = id_attribute($id, $number);

    $type = ' type="button" ';
    $code = str_replace('"', '&quot;', $code);

    if ($confirm) {
        $code = ' if(confirm(\'' . format_confirm($confirm) . '\')) { ' . $code .
            ' return true; } else { return false; } ';
    }
    $class = $class ? ' class="' . $class . '" ' : "";

    if (!$label)
        $label = $name;
    echo '<button ' . $type . $id_attribute . ' onclick="' . $code . '" ' . $class .
        ' >' . $label . '</button>';
    return $id;

}
function popup_button($label, $msg, $code = false, $class = false)
{
    $ret_text = "";
    $text_array = array();
    if ($code && gettype($code) == "string")
        $ret_text .= "<b>$code</b>";
    if (is_array($msg)) {
        parse_error_array($text_array, $msg);
    } else
        $text_array[] = $msg;

    foreach ($text_array as $key => $str) {
        if ($code)
            $str = string_to_pre($str);
        else
            $str = nl2br_if_needed($str);

        $ret_text .= "<br />$str";
    }

    $b64_text = base64_encode($ret_text);
    $jscript = "popup_64('$label','$b64_text');return false;";

    script_button($label, $jscript, false, $class);

}
function string_to_pre($str, $echo = false, $highlight = true)
{
    if (is_array($str)) {
        $retarr = array();
        parse_error_array($retarr, $str);
        $str = implode("\n", $retarr);
    }
    $str = htmlentities($str);
    $str = str_replace(" ", "&nbsp;", $str);
    $str = nl2br_if_needed($str);
    if ($highlight)
        $ret_text = "<span class=\"code_output\">\n$str</span>";
    else
        $ret_text = $str;
    if ($echo)
        echo $ret_text;
    return $ret_text;
}
function nl2br_if_needed($str)
{
    $search_array = array(
        "<br>",
        "<br />",
        "<br/>");
    foreach ($search_array as $search_string)
        if (stripos($str, $search_string) !== false)
            return $str;
    $find = array(
        "\n\r",
        "\n",
        "\r");
    $replace = "<br />";
    return str_replace($find, $replace, $str);
}
function set_jump_to_onload($set)
{
    global $jump_to_onload;
    $jump_to_onload = $set;
    return;

}
function jump_to_onload($pass = false)
{
    if ($pass) {
        global $jump_to_onload;
        if (isset($jump_to_onload))
            ihide("jump_to_onload", $pass);
        return;
    }
    echo '<script> var jump_to_onload = \'' . pv_or_blank("jump_to_onload") . '\'; </script>';
}
function ret_multivar($array)
{
    $ret_string = array();
    foreach ($array as $key => $val) {
        //$key= urlencode($key);
        //$val= urlencode($val);

        use_key($key, false);
        $ret_string[] = $key . MULTIVAR_EQUALS . $val;
    }
    return implode(MULTIVAR_DELIMITER, $ret_string);
}

function div_open_float()
{
    global $div_float_open_count;
    if (!isset($div_float_open_count))
        $div_float_open_count = 1;
    elseif (is_numeric($div_float_open_count))
        $div_float_open_count++;
    else {
        div_close_float();
        return div_open_float();
    }
    $zindex = $div_float_open_count + 50;

    switch ($div_float_open_count) {
        case 1:
            $style = "top:20px;right:20px;";
            break;
        case 2:
            $style = "top:20px;left:20px;";
            break;
        case 3:
            $style = "bottom:20px;left:20px;";
            break;
        case 4:
            $style = "bottom:20px;right:20px;";
            break;
        default:
            die("okay, too many div_open_floats up in this bitch. it's been fun");

    }
    $div_float_open_count .= "_open";
    $style .= "z-index:$zindex;";

    echo '<div style="' . $style . '" class="div_float">';

}
function div_close_float()
{
    global $div_float_open_count;
    if (!isset($div_float_open_count) || is_numeric($div_float_open_count))
        die("call div_open_float before div_close_float");

    list($div_float_open_count, $open_string) = explode("_", $div_float_open_count);

    echo '</div>';
}

function float_html($html)
{
    div_open_float();
    echo $html;
    div_close_float();
}
function get_full_url($file = "")
{
    $host = $_SERVER['HTTP_HOST'];
    $protocol = get_protocol();
    $base = $_SERVER['SCRIPT_NAME'];
    if ($file == "CURRENT")
        $file = $base;
    else
        $file = str_replace(basename($base), "", $base) . $file;

    $url = "$protocol://$host$file";

    return $url;
}
function head_tag_url_set($file)
{
    global $head_tag_url;

    $head_tag_url = get_full_url($file);

}
function echo_head_redirect()
{
    global $head_tag_url;
    if (!isset($head_tag_url))
        return;

    echo '<meta http-equiv = "refresh" content = "3; url = ' . $head_tag_url .
        '" />';

}
function help_link($help, $help_title = "", $echo = true)
{
    $url = get_full_url("CURRENT") . "?help=$help&help_title=$help_title";
    $html = '<a href="javascript:dhtmlLoadScript(' . "'$url'" .
        ');"><img src="help.png" hspace="5" /></a>';
    if ($echo)
        echo $html;


}
function get_protocol()
{
    $host = $_SERVER['HTTPS'] == "off" || !$_SERVER['HTTPS'] ? "http" : "https";
    return $host;
}
function help_hijack()
{
    if (!($help = gv_or_blank("help")))
        return;
    $help_title = gv_or_else("help_title", "");
    header('Content-Type: application/javascript');
    $text = addslashes(rethelp_text($help));
    echo "popup_help('$help_title','$text');";
    exit;
}
function help_on_load($help_string, $title = "Help")
{
    $html = rethelp_text($help_string);
    popup_msg_on_load($html, $title);
}
function popup_msg_on_load($msg, $title = "")
{
    if ($title) {
        global $popup_title;
        $popup_title = $title;
    }
    global $popup_message;
    if ($popup_message)
        $popup_message = "$msg<hr />$popup_message";
    else
        $popup_message = $msg;
}

function parse_error($msg = false, $warning = false, $show_crap = true)
{
    global $parse_errors, $line_num, $raw_line, $cur_entity, $cur_integration, $parse_errors_count;
    $parse_errors_count++;
    if ($msg === false)
        return implode('<br />', $parse_errors);
    $parse_errors_count++;
    if ($warning === true)
        $msg_type = "Warning";
    elseif ($warning === false)
        $msg_type = "Error";
    else
        $msg_type = $warning;
    if (!$warning)
        error_state(true);
    $msg_parr = false;
    if (is_array($msg)) {
        $msg_parr = $msg;
        $msg = "Array:";
        parse_error_array($parse_errors, $msg_parr);
    }

    if ($show_crap) {
        if (isset($line_num))
            $parse_errors[] = "<b>$msg_type line $line_num:</b> $msg";
        else
            $parse_errors[] = "<b>$msg_type:</b> $msg";


        if (isset($raw_line))
            $parse_errors[] = "  - LINE: $raw_line";
        if (isset($cur_entity) && $cur_entity) {
            $parse_errors[] = "  - CURRENT ENTITY";
            parse_error_array($parse_errors, $cur_entity, "  - ");

        }
        if (isset($cur_integration) && $cur_integration) {
            if (($integration_name = integ_val('name')))
                $parse_errors[] = "  - INTEGRATION: $integration_name";

            parse_error_array($parse_errors, $cur_integration, "  - ");
        }
    } else
        $parse_errors[] = "<b>$msg_type:</b> $msg";
    $parse_errors[] = "";
    return $msg;
}
function parse_error_array(&$output, $input_array, $indent = "")
{
    $indent .= "- ";
    if (!$input_array) {
        $output[] = $indent . "[empty]";
        return;
    }
    foreach ($input_array as $key => $value) {
        $output_string = "$indent$key -> ";
        switch (gettype($value)) {
            case "boolean":
                $output[] = $output_string . ($value ? "true" : "false");
                break;
            case "string":
                $output[] = $output_string . "'$value'";
                break;
            case 'integer':
            case 'double':
                $output[] = $output_string . $value;
                break;
            case 'NULL':
                $output[] = $output_string . "NULL - ** POSSIBLE BUG! **";
                break;
            case 'array':
                //$output[] = "<pre>";
                $output[] = "$output_string Array: ";
                parse_error_array($output, $value, $indent);
                //$output[] = "</pre>";

                break;
            default:
                $output[] = $output_string . '<pre>' . nl2br(var_export($value, true)) .
                    '</pre>';
                break;
        }


    }

}
function error_state($set = false)
{
    if (defined("ERROR_STATE"))
        return true;
    if ($set) {
        define("ERROR_STATE", true);
        return false;
    }
    return defined("ERROR_STATE");
}
function rethelp_text($help_string, $html = true)
{
    $help_strings = explode("/", $help_string);
    $text = "";
    foreach ($help_strings as $help_string) {
        if (strpos("..", $help_string) !== false)
            return "ERROR: help string invalid: $help_string";
        $fgc = @file_get_contents($p = HELP_DIR . "/$help_string.html");
        if (!$fgc) {
            $result = @file_put_contents($p, HELP_FILE_PLACEHOLDER_TEXT);
            return ("ERROR: help file $p not found, " . ($result ? " CREATED" :
                " COULD NOT BE CREATED"));
        }
        if ($fgc == HELP_FILE_PLACEHOLDER_TEXT)
            return ("ERROR: HELP FILE NEEDS WRITTEN");

        $text .= $fgc . "<br />";
    }

    if ($html) {
        $text = str_replace(array(
            "\r\n",
            "\n",
            "\r"), " ", $text);
    } else {
        $breaks = array(
            "<br />",
            "<br>",
            "<br/>");
        $text = str_ireplace($breaks, "\r\n", $text);
        $text = htmlentities($text);

    }
    return $text;
}

function retconst($var_name)
{
    $var_name = strtoupper($var_name);
    if (!defined($var_name))
        return false;
    return constant($var_name);
}

?>