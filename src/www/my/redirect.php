<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require('pre.php');

$Language->loadLanguageMsg('my/my');

if (isset($pv) && $pv == 2) {
    $HTML->pv_header(array());
} else {
    site_header(array('title' => $Language->getText('my_redirect', 'page_title')));
}

if(array_key_exists('return_to', $_REQUEST) && $_REQUEST['return_to'] != '') {
    // if return_to URL start with a protocol name then take as is
    // otherwise prepend the proper http protocol
    
    $return_to = trim($_REQUEST['return_to']);

    $use_ssl = session_issecure() || $GLOBALS['sys_force_ssl'];
    
    if ($use_ssl) {
        $server_url = "https://".$GLOBALS['sys_https_host'];
    } else {
        $server_url = "http://".$GLOBALS['sys_default_domain'];
    }
    
    if (preg_match("/^[A-Za-z]+:\/\//i", $return_to)) {
        $return_url = $return_to;
    } else {	
        $return_url = $server_url.$return_to;		
    }
    
        	 
    $redirect = $Language->getText('my_redirect', 'return_to', array($return_url));
    
    print '
<script language="JavaScript"> 
<!--
function return_to_url() {
  window.location="'.$return_url.'";
}

setTimeout("return_to_url()",1000);
//--> 
</script>
';	 
}
else {
    $redirect = $Language->getText('my_redirect', 'default_txt');
}
?>

<p><big><?= $redirect; ?></big></p>

<?
(isset($pv) && $pv == 2) ? $HTML->pv_footer(array()) : site_footer(array());
?>