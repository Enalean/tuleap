<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
// Originally written by Laurent Julliard 2004, Codendi Team, Xerox
//

require_once('pre.php');


if (!(user_isloggedin() && user_is_super_user())) {
    exit_error('ERROR',$Language->getText('admin_approve_license', 'error'));
}

$legal_acceptance = $request->get('legal_acceptance');

$HTML->header(array('title'=>$Language->getText('admin_approve_license', 'title',array($GLOBALS['sys_name'])), 'main_classes' => array('tlp-framed')));


    if ($legal_acceptance == 'ACCEPT') {

	license_accepted();
	echo license_msg_accepted();

    } else if ($legal_acceptance == 'DECLINE') {

	license_declined();
	echo '<p><span class="highlight">'.license_msg_declined().'</span></p>';

    } else {

        echo '<FORM ACTION="?" METHOD="POST" name="license_form">'.
	    "\n<table><tr><td>\n";

	// Preamble

	echo '<p>'.$Language->getText('admin_approve_license', 'msg_accept');

	// display the license and the agree/disagree buttons
	include($Language->getContent('admin/license_terms'));

        echo '</td></tr>
             <tr VALIGN="MIDDLE" class="boxtitle">
                 <td ALIGN="RIGHT"><b>&nbsp;&nbsp  </b>
                    <input TYPE="RADIO" name="legal_acceptance" value="ACCEPT"><b>'.$Language->getText('admin_approve_license', 'accept').'</b>&nbsp;&nbsp;
                   <input TYPE="RADIO" name="legal_acceptance" value="DECLINE"><b>'.$Language->getText('admin_approve_license', 'decline').'</b>&nbsp;&nbsp;
                 </td>
             </tr>
             <tr VALIGN="MIDDLE">
                 <td ALIGN="RIGHT"><b>&nbsp;&nbsp  </b>
            <input type="submit" name="continueShopping_0"  border="0" class="tlp-button-primary" value="'.$Language->getText('admin_approve_license', 'continue').'">
                </td>
             </tr>
       </table>';

	echo '</form>';
    }

$HTML->footer(array());

?>
