<?php
declare(strict_types= 1);// enforces paramater types
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
this function is to prevent session highjacking (security)
make sure to call this where sessions are used
# EXAMPLE:
    $seconds = 60;
    $minutes = 60;
    $hours = 24;
    $expiresAt = $seconds * $minutes * $hours;
    refreshToken($expiresAt)

make sure to require_once this file and call the refreshToken() function
*/

function refreshToken(INT $expirationTime) {
    if(!isset($_SESSION["iat"])) {
        $_SESSION["iat"] = time();
    }elseif (time() - $_SESSION["iat"] > $expiresAt) {
        session_regenerate_id(true);
        $_SESSION["iat"] = time();
    }
}

/*
this function exists to read config files in the .ini format
a error is returned if no config file was found
*/

function get_conf(String $path = "./config.ini") {// default directory for config file
    try {
        $conf = parse_ini_file($path);
        return $conf;// return config if succsessfull
    }
    catch (Exception $e) {// could not find config file
        echo "Caught exception: ", $e->getMessage(), "\n No config file was found!";
        exit;
    }
}

// this function removes html inside a string
function strip_html(string $html) {
    $strippedHtml = htmlspecialchars(strip_tags($html));
    return $strippedHtml;
}