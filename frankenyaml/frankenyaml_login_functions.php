<?php


/**
 * Very simple login doohickey for people who trust me with their data.

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/

defined("IN_YAML_HELPER") || die("not in helper");

function auth()
{
    if (defined('AUTH'))
        return AUTH;
    while (true) {
        $login_type = false;
        if (post_logout()) {
            break;
        }
        if ($user = cookie_login()) {
            $login_type = "cookie";
            break;
        }
        if ($user = post_login()) {
            $login_type = "post";
            break;
        }
        break;
    }
    if ($login_type)
        define('USERNAME', $user);
    else {
        define('USERNAME', "guest");
        uid(true);
    }
    define("AUTH", $login_type);
    return AUTH;
}
function login_button()
{
    if(auth())
    {
        $multivar = array("new_mode"=>"login","new_login_mode"=>"login","logout"=>"yep");
        isubmit_multi($multivar,"Log Out");
    }
    else
    {
        $multivar = array("new_mode"=>"login","new_login_mode"=>"login");
        isubmit_multi($multivar,"Login");
    }
        
}
function save_entities_file($disp_entities = false, $uid = false)
{
    if ($disp_entities === false) {
        global $disp_entities;
        if (!isset($disp_entities))
            $disp_entities = array();
    }
    if (!is_array($disp_entities))
        die("Could not save entities file, not disp_entities array " . var_export($disp_entities, true));

    $uid = $uid ? $uid : uid();
    if (!($bd = build_dir($uid)))
        die("Error: something messed up figuring out the build dir");
    return build_file("$bd/" . SAVE_FILENAME, encrypt_array($disp_entities), $uid);

}
function get_entities_file($uid = false)
{
    $uid = $uid ? $uid : uid();
    $fn = valid_entities_file($uid);

    if ($raw = @file_get_contents($fn)) {
        global $disp_entities;
        if (is_array($disp_entities = decrypt_array($raw)))
            return $disp_entities;
        else {
            echo "DISPLAY ENTITIES  CORRUPTED : FN: $fn<br />";
            return false;
        }
    }
    echo "DISPLAY ENTITIES COULD NOTE BE OPENED: FN: $fn";
    return false;
}
function get_display_entities()
{
    global $disp_entities, $missing_files;
    if (auth() && get_entities_file())
        return;

    if (!pv_or_blank('disp_entities_serialized')) {
        $disp_entities = $missing_files = array();
        return;
    }
    $disp_entities = @unserialize(@base64_decode(pv_or_blank('disp_entities_serialized')));
    if (!is_array($disp_entities))
        die("FATAL: could not unserialize passed array: <pre>" . pv_or_blank('disp_entities_serialized'));

    if (isset($disp_entities['configuration']) && isset($disp_entities['configuration']['missing_files']))
        $missing_files = $disp_entities['configuration']['missing_files'];
}
function save_display_entities()
{
    global $disp_entities;
    if (!isset($disp_entities) || !is_array($disp_entities)) {
        echo "probably a bug: could not save big array<br />";
        return;
    }
    if (auth() && save_entities_file())
        return;

    ihide("disp_entities_serialized", base64_encode(serialize($disp_entities)));
}
function encrypt_array($array)
{
    $string = serialize($array);

    return openssl_encrypt($string, "AES-128-CTR", encryption_key(), 0,
        INITIALIZATION_VECTOR);
}
function decrypt_array($string)
{
    $serialized = openssl_decrypt($string, "AES-128-CTR", encryption_key(), 0,
        INITIALIZATION_VECTOR);
    if (!$serialized) {
        echo "Failed to decrypt saved, sorry buddy<br />";
        return array();
    }

    return unserialize($serialized);
}
function encryption_key($set = false)
{
    if ($set && defined("ENCRYPTION_KEY"))
        die("could not set encryption key, already set");
    if ($set)
        return define("ENCRYPTION_KEY", $set);
    if (!defined("ENCRYPTION_KEY"))
        die("could not return encryption key, not set yet");
    return ENCRYPTION_KEY;

}
function post_logout()
{
    if (pv_or_blank('logout')) {
        setC("user", "");
        setC("passwordmd5", "");
        parse_error("You are now logged out", true);
        return true;
    }
    return false;
}
function delete_login($uid)
{
    if (!pv_or_blank("delete_login"))
        return false;
    if (!pv_or_blank("delete_login_confirm")) {
        parse_error("Delete Login Needs to be confirmed");
        return false;
    }

}
function post_login()
{
    $uid = false;
    if (pv_or_blank("register_time")) {
        $user = pv_or_blank('user');
        $pass = pv_or_blank('password1');
        $pass2 = pv_or_blank('password2');
        if (!$pass) {
            parse_error("Fill in the password, it was blank");
            return false;
        }
        if ($pass != $pass2) {
            parse_error("Password fields do not match");
            return false;
        }
        $passwordmd5 = md5($pass);
        $uid = uid_from_login($user, $passwordmd5);
        if ($uid && valid_entities_file($uid)) {
            parse_error("already created, you can login");
            return false;
        }
        encryption_key($passwordmd5);

        uid($uid);
        save_entities_file(array(), $uid);
        setC("user", $user);
        setC("passwordmd5", $passwordmd5);


        parse_error("User $user Created. Do not lose your password '$pass', you cannot recover it!!<br />" .
            "Yes I just showed your password back to you, deal with it.<br />" .
            "FYI: Your account will be automatically deleted and all your saved stuff as well if you do not login for " .
            IDLE_ACCOUNT_DELETE_DAYS . " Days.", true);
        return $user;

    }
    if ( pv_or_blank('login_time') ) {
        $user = pv_or_blank('user');
        $pass = pv_or_blank('password');
        $passwordmd5 = md5($pass);
        $uid = uid_from_login($user, $passwordmd5);
        if (!valid_entities_file($uid)) {
            parse_error("Login Failed");
            return false;
        }
        setC("user", $user);
        setC("passwordmd5", $passwordmd5);
        encryption_key($passwordmd5);

        uid($uid);
        return $user;

    }
    return false;
}
function cookie_login()
{

    $uid = cookie_uid();
    if ($uid && valid_entities_file($uid)) {

        encryption_key(getC("passwordmd5"));
        uid($uid);
        return getC("user");
    }

    return false;
}
function cookie_uid()
{
    if (($u = getC("user")) && ($p = getC("passwordmd5")))
        return uid_from_login($u, $p);
    return false;
}

function uid_from_login($user, $passwordmd5)
{
    $uid = md5($user . $passwordmd5);
    return $uid;
}
function valid_entities_file($uid = false)
{

    $uid = $uid ? $uid : uid();
    if (!file_exists($bd = build_dir($uid)))
        return false;
    if (!file_exists($fn = "$bd/" . SAVE_FILENAME))
        return false;
    return $fn;
}
function uid($set = false)
{
    if ($set && defined("UID"))
        die("cannot set uid to $set, already set to " . UID);
    if (!$set and !defined("AUTH"))
        die("Cannot get UID before AUTH run");

    if (defined("UID"))
        return UID;
    if (!$set)
        die("Cannot return UID, not set yet");
    if ($set !== true)
        $uid = $set;
    else {
        if (!($uid = getC('uid')))
            $uid = md5(time() . $_SERVER['REMOTE_ADDR'] . rand(5, 1000));
    }
    define("UID", $uid);
    setC("uid", UID);
}
function getC($name = "")
{
    global $cookie_cache;
    if ($name) {

        if (!in_array($name, COOKIE_KEYS))
            die("cannot getC $name -- not a valid variable name");
        if (!isset($cookie_cache) || !is_array($cookie_cache))
            getC();
        return $cookie_cache[$name];

    }
    if (isset($cookie_cache) && is_array($cookie_cache))
        return;
    $cookie_cache = array();
    $debug = "";
    foreach (COOKIE_KEYS as $key) {
        $cookie_cache[$key] = "";
        if (isset($_COOKIE[cK($key)]) && $_COOKIE[cK($key)])
            $cookie_cache[$key] = $_COOKIE[cK($key)];

        $debug .= cK($key) . " $key set to {$cookie_cache[$key]} <br />";
    }
    //foot($debug);
}
function issetC($name)
{
    $keys = COOKIE_KEYS;
    if(!in_array($name,$keys))
        die("Cookie name invalid in issetC: $name<br />".var_export($keys,true));
        
    return isset($_COOKIE[cK($name)]);
}
function setC($name, $value=false)
{
    if (headsent())
        die("Cannot set cookie $name, header sent");
    getC();
    global $cookie_cache;
    if ($name === false) {
        if (defined("SET_C_RUN"))
            echo "WARNING: I am clearing cookies now but I was told previously to set a cookie: " .
                var_export($cookie_cache, true) . "<br />";
        foreach(COOKIE_KEYS as $key)
            $cookie_cache[$key] = "";
        return;
    }
    defined("SET_C_RUN") || define("SET_C_RUN", true);

    if (!in_array($name, COOKIE_KEYS))
        die("cannot setC $name -- not a valid variable name");
    if (is_bool($value))
        $value = $value ? 1 : 0;
    $cookie_cache[$name] = $value;

}
function saveC($clear = false, $duration = 30)
{
    if (headsent())
        die("Cannot save cookies cookie, header sent");
    headsent(true);
    global $cookie_cache;
    getC();
    if (!is_array($cookie_cache))
        die("Cannot save cookies , cookies not loaded");
    $debug = "";
    foreach (COOKIE_KEYS as $key) {
        $val = isset($cookie_cache[$key]) && $cookie_cache[$key] && !$clear ? $cookie_cache[$key] :
            "";
        setcookie(cK($key), $val, time() + (86400 * $duration), '/'); // 86400 = 1 day
        $debug .= cK($key) . " $key set to $val <br />";
    }
    //foot($debug);


}
function cK($key)
{
    if (strpos($key, COOKIE_NAME) === 0)
        return substr($key, strlen(COOKIE_NAME) + 1);
    return COOKIE_NAME . "_$key";
}

function headsent($set = false)
{
    if ($set) {
        if (defined('HEADECHOED'))
            return true;
        define('HEADECHOED', true);
        return true;
    }
    return defined('HEADECHOED');
}


?>