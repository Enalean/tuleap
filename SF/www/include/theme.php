<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//

// define the theme
if (isset($HTTP_COOKIE_VARS["SF_THEME"])&&(user_getid() == (int)(substr($HTTP_COOKIE_VARS["SF_THEME"],0,6))) ) {
    // define the global var $theme
    $theme = substr($HTTP_COOKIE_VARS["SF_THEME"],6);
} else {
    // No cookie defined
    // Read the user preferences
    $res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
    $row_user = db_fetch_array($res_user);
    if ( $row_user['theme'] == "" || $row_user['theme'] == "default") {
	// Use the defaut theme
	$theme = $sys_themedefault;
    } else {
	$theme = $row_user['theme'];
    }
    // Define the cookie to improve the performance for the next access
    setcookie("SF_THEME", sprintf("%06d%s",user_getid(),$theme), time() + 60*60*24*365, "/");
}
// Initialize the global sys_user_theme variable
$GLOBALS['sys_user_theme'] = $theme;

// Find where the path is located
$GLOBALS['sys_is_theme_custom'] = is_dir(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/themes/css/'.$theme);

// define the font size cookie for performance
if ( (isset($HTTP_COOKIE_VARS["SF_FONTSIZE"]))&&(user_getid() == (int)(substr($HTTP_COOKIE_VARS["SF_FONTSIZE"],0,6))) ) {
    // define the global var $font_size
    $font_size = (int)(substr($HTTP_COOKIE_VARS["SF_FONTSIZE"],6));
} else {
    // No cookie defined
    // Read the user preferences
    
    // Check if we have already read the record
    if ( !$res_user ) {
	$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
	$row_user = db_fetch_array($res_user);
    }
    
    if ( $row_user['fontsize'] <> 0 ) {
	$font_size = $row_user['fontsize'];
    } else {
	// Use the defaut fontsize
	//determine font for this platform
	if (browser_is_windows() && browser_is_ie()) {
	    $font_size = 2;
	} else if (browser_is_windows()) {
	    //netscape on wintel
	    $font_size = 2;
	} else if (browser_is_mac()){
	    //mac users need bigger fonts
	    $font_size = 2;
	} else {
	    $font_size = 2;
	}
    }
    // Define the cookie to improve the performance for the next access
    setcookie("SF_FONTSIZE", sprintf("%06d%d",user_getid(),$font_size), time() + 60*60*24*365, "/");      
    }
// Initialize the global sys_user_font_size variable
$GLOBALS['sys_user_font_size'] = $font_size;

?>
