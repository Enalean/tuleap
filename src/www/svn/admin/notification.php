<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

$Language->loadLanguageMsg('svn/svn');

// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
if (isset($post_changes)) {
    //sanity checks
    $valid_user = true;
    $valid_subdir = true;
    if ($subdirs != NULL && $users != NULL) {
        if (in_array("",$subdirs) || in_array("",$users)) {
            //no empty fields
	    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification','empty_field'));
	    $valid_user = false;
	    $valid_subdir = false;
        } else {
            //deny invalid email addresses
            while (list(, $user) = each($users)) {
	        if (!validate_emails($user)) {
		    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification','email_invalid',array($user)));
		    $valid_user = false;
	        }
	    }
	    //deny invalid paths
	    while (list(, $subdir) = each($subdirs)) {
	        if ($subdir == "/") {
		    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification','path_invalid',array($subdir)));
		    $valid_subdir = false;
		}
	    }
        }
    }
    
    if ($valid_user && $valid_subdir) {
        svn_data_update_advanced_notif($group_id,$subdirs,$users);
        $ret = svn_data_update_notification($group_id,$form_mailing_list,$form_mailing_header);
        if ($ret) {
	    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('svn_admin_notification','upd_success'));
        } else {
	    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification','upd_fail',db_error()));
        }
    }
}

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_general_settings','gen_settings'),
		      'help' => 'SubversionAdministrationInterface.html#SubversionEmailNotification'));

?>

<script type="text/javascript">
<!--

function addEvent(subdir,user)
{
  var ni = document.getElementById('svn_notif');
  var numi = document.getElementById('svn_val');
  var num = (document.getElementById("svn_val").value -1)+ 2;
  numi.value = num;
  var divIdName = "svn_notif_"+num+"_div";
  var newdiv = document.createElement('div');

  newdiv.setAttribute("id",divIdName);
  newdiv.innerHTML += "<table><tr><td align=center width=328><input name='subdirs["+num+"]' type='text' value='"+subdir+"' size=42 /></td><td align=center width=328><input name='users["+num+"]'' type='text' value='"+user+"' size=42 /></td><td align=center><a href=\"javascript:;\" onclick=\"removeEvent(\'"+divIdName+"\')\"><img src=\"<?php echo util_get_image_theme("ic/trash.png")?>\"></a></td></tr></table>";
  ni.appendChild(newdiv);
}

function removeEvent(divNum)
{
  var d = document.getElementById('svn_notif');
  var olddiv = document.getElementById(divNum);
  d.removeChild(olddiv);
}

//-->
</script>
		      
<?php		      
$project=project_get_object($group_id);
$svn_mailing_list = $project->getSVNMailingList();
$svn_mailing_header = $project->getSVNMailingHeader();

echo '
       <H2>'.$Language->getText('svn_admin_notification','email').'</H2>
       <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="notification">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
       '.$Language->getText('svn_admin_notification','mail_comment').'

       <P><b>'.$Language->getText('svn_admin_notification','mail_to').'</b></p><p><INPUT TYPE="TEXT" SIZE="70" NAME="form_mailing_list" VALUE="'.$svn_mailing_list.'"></p>

       <p><b>'.$Language->getText('svn_admin_notification','header').'</b></p>
       <p><INPUT TYPE="TEXT" SIZE="20" NAME="form_mailing_header" VALUE="'.$svn_mailing_header.'"></p>
	<hr><p>'.$Language->getText('svn_admin_notification','mail_commit_subtree').'</p>

	<table border="0"><tr class="'.util_get_alt_row_color(0).'">
	<td width=328 align=center><b>'.$Language->getText('svn_admin_notification','path_subdir').'</b></td>
	<td width=328 align=center><b>'.$Language->getText('svn_admin_notification','users').'</b></td>
	<td><b>'.$Language->getText('svn_admin_notification','del').'</b></td></tr></table>

	<input type="hidden" value="0" id="svn_val" />
	<div id="svn_notif"></div>';
	
	$result = svn_data_get_advanced_notif($group_id);
	if (db_numrows($result) > 0) {	    
	    while ($rows = db_fetch_array($result)) {
	        $dirs = $rows['svn_dir'];
		    $user = $rows['svn_user'];
		echo '<script langauge="javascript">addEvent("'.$dirs.'","'.$user.'");</script>';
	    }
	}
	
echo 	'
	<p><a href="javascript:;" onclick="addEvent(\'\',\'\');">'.$Language->getText('svn_admin_notification','add_new_entry').'</a></p>

        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'"></p></FORM>';

svn_footer(array());
?>
