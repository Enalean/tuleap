<?php
 /**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Jean-Philippe Giola, 2005
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/*
 * ForumML New Thread submission form
 * 
 */ 
 
require_once('pre.php');
require_once('forumml_utils.php');
require_once('www/mail/mail_utils.php');

$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('forumml');
if ($p && $plugin_manager->isPluginAvailable($p) && $p->isAllowed()) {

	$request =& HTTPRequest::instance();
	
	if ($request->valid(new Valid_UInt('group_id'))) {
		$group_id = $request->get('group_id');
	} else {
		$group_id = "";
	}
	
	// Checks 'list' parameter
	if (! $request->valid(new Valid_UInt('list'))) {
		exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','specify_list'));
	} else {
		$list_id = $request->get('list');
		if (!user_isloggedin() || (!mail_is_list_public($list_id) && !user_ismember($group_id))) {
			exit_error($GLOBALS["Language"]->getText('include_exit','info'),$GLOBALS["Language"]->getText('include_exit','mail_list_no_perm'));
		}
		if (!mail_is_list_active($list_id)) {
			exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','wrong_list'));
		}
	}

        $message_posted = false;
	// If message is posted, send a mail	
	if ($request->isPost() && $request->exist('post')) {
		// Checks if mail subject is empty
		$vSub = new Valid_String('subject');
		$vSub->required();
		if (! $request->valid($vSub)) {		
			$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_forumml','type_subject'));
		} else {
			// process the mail
			$return = plugin_forumml_process_mail($p);
			if ($return) {
				$message_posted = true;
				$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_forumml','delay_redirection',array($p->getThemePath()."/images/ic/spinner-greenie.gif",$group_id,$list_id,0)), CODENDI_PURIFIER_DISABLED);
			}
		}
	} else {
		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_forumml','warn_post_without_confirm'));
	}

	$params['title'] = 'ForumML';
	$params['group'] = $group_id;
	$params['toptab'] = 'mail';
	$params['help'] = "communication.html#mailing-lists";
	mail_header($params);

        if ($message_posted) {
            // wait few seconds before redirecting to archives page
            echo "<script> setTimeout('window.location=\"/plugins/forumml/message.php?group_id=".$group_id."&list=".$list_id."\"',3000) </script>";
        }

	$list_link = '<a href="/plugins/forumml/message.php?group_id='.$group_id.'&list='.$list_id.'">'.mail_get_listname_from_list_id($list_id).'</a>';
	echo '<H2><b>'.$GLOBALS['Language']->getText('plugin_forumml','list_new_thread',array($list_link)).'</b></H2>
	<a href="/plugins/forumml/message.php?group_id='.$group_id.'&list='.$list_id.'">['.$GLOBALS["Language"]->getText('plugin_forumml','browse_arch').']</a><br><br>
	<H3><b>'.$GLOBALS['Language']->getText('plugin_forumml','new_thread').'</b></H3>';

	// New thread form
	echo '<script type="text/javascript" src="scripts/cc_attach_js.php"></script>';
	echo "<form name='form' method='post' enctype='multipart/form-data'>
	<table>
    <tr>
		<td valign='top' align='left'><b> ".$GLOBALS['Language']->getText('plugin_forumml','subject').":&nbsp;</b></td>
		<td align='left'><input type=text name='subject' size='80'></td>
	</tr></table>";
	echo '<table>
    <tr>
		<td align="left">
			<p><a href="javascript:;" onclick="addHeader(\'\',\'\',1);">['.$GLOBALS["Language"]->getText('plugin_forumml','add_cc').']</a>
			 - <a href="javascript:;" onclick="addHeader(\'\',\'\',2);">['.$GLOBALS["Language"]->getText('plugin_forumml','attach_file').']</a></p>
			<input type="hidden" value="0" id="header_val" />
			<div id="mail_header"></div></td></tr></table>';
	echo "<table><tr>
			<td valign='top' align='left'><b>".$GLOBALS['Language']->getText('plugin_forumml','message')."&nbsp;</b></td>
			<td align='left'><textarea rows='20' cols='100' name='message'></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' name='post' value='".$GLOBALS['Language']->getText('global','btn_submit')."'>
				<input type='reset' value='".$GLOBALS['Language']->getText('plugin_forumml','erase')."'></td>
		</tr>
	</table></form>";

	mail_footer($params);

} else {
	header('Location: '.get_server_url());
}

?>
