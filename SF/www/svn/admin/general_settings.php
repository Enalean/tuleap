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

$LANG->loadLanguageMsg('svn/svn');

// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
if ($post_changes) {
    $ret = svn_data_update_general_settings($group_id,$form_tracked,$form_preamble);
    if ($ret) {
	$GLOBALS['feedback'] = $LANG->getText('svn_admin_general_settings','upd_success');
    } else {
	$GLOBALS['feedback'] = $LANG->getText('svn_admin_general_settings','upd_fail',db_error());
    }
}

// Display the form
svn_header_admin(array ('title'=>$LANG->getText('svn_admin_general_settings','gen_settings'),
		      'help' => 'SubversionAdministrationInterface.html#SubversionGeneralSettings'));

$project=project_get_object($group_id);
$svn_tracked = $project->isSVNTracked();
$svn_preamble = $project->getSVNPreamble();

echo '
       <H2>'.$LANG->getText('svn_admin_general_settings','gen_settings').'</H2>
       <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="general_settings">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
       <h3>'.$LANG->getText('svn_admin_general_settings','tracking').'</H3><I>
       <p>'.$LANG->getText('svn_admin_general_settings','tracking_comment',$GLOBALS['sys_name']).
    '</I>
       <p><b>'.$LANG->getText('svn_admin_general_settings','tracking').'</b>&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="form_tracked">
       <OPTION VALUE="1"'.(($svn_tracked == '1') ? ' SELECTED':'').'>'.$LANG->getText('global','on').'</OPTION>
       <OPTION VALUE="0"'.(($svn_tracked == '0') ? ' SELECTED':'').'>'.$LANG->getText('global','off').'</OPTION>       </SELECT></p>
       <br>'.$LANG->getText('svn_admin_general_settings','preamble',array('/svn/?func=info&group_id='.$group_id,$GLOBALS['sys_name'])).'
       <BR>
       <TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.$svn_preamble.'</TEXTAREA>
        </p>
        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$LANG->getText('global','btn_submit').'"></p></FORM>';

svn_footer(array());
?>
