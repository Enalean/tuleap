<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 


unset ($GLOBALS['BROWSER_AGENT']);
unset ($GLOBALS['BROWSER_VER']);
unset ($GLOBALS['BROWSER_PLATFORM']);

function browser_get_agent () {
	return $GLOBALS['BROWSER_AGENT'];
}

function browser_get_platform() {
	return $GLOBALS['BROWSER_PLATFORM'];
}

function browser_is_mac() {
	if (browser_get_platform()=='Mac') {
		return true;
	} else {
		return false;
	}
}

function browser_is_windows() {
	if (browser_get_platform()=='Win') {
		return true;
	} else {
		return false;
	}
}

function browser_is_ie() {
	if (browser_get_agent()=='IE') {
		return true;
	} else {
		return false;
	}
}

function browser_is_netscape() {
	if (browser_get_agent()=='MOZILLA') {
		return true;
	} else {
		return false;
	}
}
function browser_is_netscape4() {
	if (browser_get_agent()=='NETSCAPE4') {
		return true;
	} else {
		return false;
	}
}


/*
	Determine browser and version
*/
$HTTP_USER_AGENT = '';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
}

if (preg_match('/MSIE ([0-9].[0-9]{1,2})/',$HTTP_USER_AGENT,$log_version)) {
	$GLOBALS['BROWSER_VER']=$log_version[1];
	$GLOBALS['BROWSER_AGENT']='IE';
} elseif (preg_match('/Opera ([0-9].[0-9]{1,2})/',$HTTP_USER_AGENT,$log_version)) {
	$GLOBALS['BROWSER_VER']=$log_version[1];
	$GLOBALS['BROWSER_AGENT']='OPERA';
} elseif (preg_match('#Mozilla/([0-9].[0-9]{1,2})#',$HTTP_USER_AGENT,$log_version)) {
	$GLOBALS['BROWSER_VER']=$log_version[1];
        if (preg_match('/^4/',$GLOBALS['BROWSER_VER'])) {
         	$GLOBALS['BROWSER_AGENT']='NETSCAPE4';
        } else {
            $GLOBALS['BROWSER_AGENT']='MOZILLA';
        }
} else {
	$GLOBALS['BROWSER_VER']=0;
	$GLOBALS['BROWSER_AGENT']='OTHER';
}

/*
	Determine platform
*/

if (strstr($HTTP_USER_AGENT,'Win')) {
	$GLOBALS['BROWSER_PLATFORM']='Win';
} else if (strstr($HTTP_USER_AGENT,'Mac')) {
	$GLOBALS['BROWSER_PLATFORM']='Mac';
} else if (strstr($HTTP_USER_AGENT,'Linux')) {
	$GLOBALS['BROWSER_PLATFORM']='Linux';
} else if (strstr($HTTP_USER_AGENT,'Unix')) {
	$GLOBALS['BROWSER_PLATFORM']='Unix';
} else {
	$GLOBALS['BROWSER_PLATFORM']='Other';
}

?>
