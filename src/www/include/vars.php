<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');

$LICENSE = array();
$LICENSE['xrx'] = $Language->getText('include_vars','policy',array($GLOBALS['sys_org_name']));
$LICENSE['website'] = $Language->getText('include_vars','website_only');
$LICENSE['other'] = $Language->getText('include_vars','other');
$LICENSE['---'] = '--- '.$Language->getText('include_vars','comip',array($GLOBALS['sys_org_name'])).' ---';
$LICENSE['gpl'] = $Language->getText('include_vars','gpl');
$LICENSE['lgpl'] = $Language->getText('include_vars','lgpl');
$LICENSE['bsd'] = $Language->getText('include_vars','bsdl');
$LICENSE['mit'] = $Language->getText('include_vars','mitxl');
$LICENSE['artistic'] = $Language->getText('include_vars','artistic_l');
$LICENSE['mpl'] = $Language->getText('include_vars','moz_l');
//$LICENSE['qpl'] = $Language->getText('include_vars','qtp_l');
//$LICENSE['ibm'] = $Language->getText('include_vars','ibm_l');
//$LICENSE['python'] = $Language->getText('include_vars','p_l');
$LICENSE['public'] = $Language->getText('include_vars','public_domain');


/*

//
//   deprecated stuff from the old software map
//

$SOFTENV = array();
$SOFTENV[1] = $Language->getText('include_vars','other_env');
$SOFTENV[2] = 'Linux/Unix Console';
$SOFTENV[3] = 'Linux/Unix X/Graphical';
$SOFTENV[4] = 'Windows';
$SOFTENV[5] = $Language->getText('include_vars','web_env');
$SOFTENV[6] = 'MacOS';
$SOFTENV[7] = 'PalmOS';
$SOFTENV[8] = 'BeOS';

$SOFTLANG = array();
$SOFTLANG[1] = $Language->getText('include_vars','other_lang');
$SOFTLANG[2] = 'C';
$SOFTLANG[3] = 'C++';
$SOFTLANG[4] = 'Perl';
$SOFTLANG[5] = 'PHP';
$SOFTLANG[6] = 'Python';
$SOFTLANG[7] = 'Unix Shell';
$SOFTLANG[8] = 'Java';
$SOFTLANG[9] = 'AppleScript';
$SOFTLANG[10] = 'Visual Basic';
$SOFTLANG[11] = 'TCL';
$SOFTLANG[12] = 'Lisp';

$ENVFILE = array();
$ENVFILE[1] = 'env-oth.png';
$ENVFILE[2] = 'env-tux.png';
$ENVFILE[3] = 'env-x.png';
$ENVFILE[4] = 'env-win.png';
$ENVFILE[5] = 'env-web1.png';
$ENVFILE[6] = 'env-mac.png';
$ENVFILE[7] = 'env-palm.png';
$ENVFILE[8] = 'env-be.png';

$ENVLINK = array();
$ENVLINK[2] = 'http://www.linux.com';
$ENVLINK[3] = 'http://www.x.org';
$ENVLINK[4] = 'http://www.windows.com';
$ENVLINK[6] = 'http://www.apple.com';
$ENVLINK[7] = 'http://www.palm.com';
$ENVLINK[8] = 'http://www.beos.com';

*/

$SHELLS = array();
$SHELLS[1] = '/bin/bash';
$SHELLS[2] = '/bin/sh';
$SHELLS[3] = '/bin/ksh';
$SHELLS[4] = '/bin/tcsh';
$SHELLS[5] = '/bin/csh';

?>
