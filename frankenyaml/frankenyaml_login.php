<?php
/**
 * This is the login page to display
 * It's actually not even necessary to run the program
 * post handling is done elsewhere, so deleting this
 * will not actually eliminate the ability to login etc.

 * Good luck reading it! Comments are for suckers!
 * - DrDS

 **/


defined("IN_YAML_HELPER") || die("not in helper");

if (!($login_mode = pv_or_blank('new_login_mode')))
    $login_mode = pv_or_else('login_mode', 'login');

ihide("login_mode", $login_mode);
ihide("mode", "login");

if (AUTH)
    $login_mode = "auth";

switch ($login_mode) {
    default:
    case 'login':
        echo '<table><tr><th>Login</th></tr>';
        echo '<tr><td>';
        itext("user", "", "Username:", "Ex: bigguy42");
        ipassword("password");
        isubmit("login_time", 1, "Login");
        echo '<hr width="200" />';
        isubmit("new_login_mode", "register", "Register New");


        break;
    case 'register':
        itext("user", "", "Username:", "Ex: bigguy42");
        ipassword("password1");
        ipassword("password2");
        isubmit("register_time", 1, "Register");
        break;
    case 'auth':

        echo "<h2>You are logged in as " . USERNAME . "</h2>";
        echo '<a href="./">Click here to go back</a><br />';
        isubmit("logout", "yep", "Logout");
        break;
}

if ($errors_string = parse_error()) {
    popup_msg_on_load($errors_string, "Configuration Errors/Warnings");
}

?>