<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
//
require_once 'browser.php';

// Read the user preferences
$theme = $current_user->getTheme();
if ($theme == "" || $theme == "default") {
    // Use the defaut theme
    if (browser_is_netscape4() && array_key_exists('sys_themedefault_old', $GLOBALS)) {
        $theme = $GLOBALS['sys_themedefault_old'];
    }
    else {
        $theme = $GLOBALS['sys_themedefault'];
    }
}

// Initialize the global sys_user_theme variable
$GLOBALS['sys_user_theme'] = $theme;

// Find where the path is located
$GLOBALS['sys_is_theme_custom'] = is_dir($GLOBALS['sys_custom_themeroot'].'/'.$theme);

// define the font size cookie for performance
$font_size = $current_user->getFontSize();
if (!$font_size) {
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
// Initialize the global sys_user_font_size variable
$GLOBALS['sys_user_font_size'] = $font_size;

?>
