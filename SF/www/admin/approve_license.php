<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

require($DOCUMENT_ROOT.'/include/pre.php');

$HTML->header(array('title'=>'CodeX License Terms - Agreement'));

if (user_isloggedin() && user_is_super_user()) {

    if ($legal_acceptance == 'ACCEPT') {

	license_accepted();
	echo license_msg_accepted();

    } else if ($legal_acceptance == 'DECLINE') {

	license_declined();
	echo '<p><span class="highlight">'.license_msg_declined().'</span></p>';

    } else {

        echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" name="license_form">'.
	    "\n<table><tr><td>\n";

	// Preamble

	echo '<p>Please indicate whether you accept or do not accept the following
             software license agreement(s) by choosing either "Accept" or "Decline" and
             clicking the "Continue" button at the bottom of the page.';
 
	// display the license and the agree/disagree buttons
	include(util_get_content('admin/codex_license_terms'));

        echo '</td></tr>
             <tr VALIGN="MIDDLE" class="boxtitle">
                 <td ALIGN="RIGHT"><b>&nbsp;&nbsp  </b>
                    <input TYPE="RADIO" name="legal_acceptance" value="ACCEPT"><b>Accept</b>&nbsp;&nbsp;
                   <input TYPE="RADIO" name="legal_acceptance" value="DECLINE"><b>Decline</b>&nbsp;&nbsp;
                 </td>
             </tr>
             <tr VALIGN="MIDDLE">
                 <td ALIGN="RIGHT"><b>&nbsp;&nbsp  </b>
            <input type="submit" name="continueShopping_0"  border="0" class="buttonblue" value="Continue"
                </td>
             </tr>
       </table>';
 
	echo '</form>';
    }

} else {
    exit_error('ERROR','Only site adminstrators can browse this page');
}

$HTML->footer(array());

?>
