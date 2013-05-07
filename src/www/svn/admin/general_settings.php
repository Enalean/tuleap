<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
//	Originally written by Laurent Julliard 2004, Codendi Team, Xerox
//


// CAUTION!!
// Make the changes before calling svn_header_admin because 
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
//
$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));
if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vTracked = new Valid_WhiteList('form_tracked', array('0', '1'));
    $vTracked->required();
    $vMandatoryRef = new Valid_WhiteList('form_mandatory_ref', array('0', '1'));
    $vMandatoryRef->required();
    $vPreamble = new Valid_Text('form_preamble');
    $vCanChangeSVNLog = new Valid_WhiteList('form_can_change_svn_log', array('0', '1'));
    $vCanChangeSVNLog->required();

    if($request->valid($vTracked) && $request->valid($vPreamble) && $request->valid($vMandatoryRef) && $request->valid($vCanChangeSVNLog)) {
        // group_id was validated in index.
        $form_tracked = $request->get('form_tracked');
        $form_preamble = $request->get('form_preamble');
        $form_mandatory_ref = $request->get('form_mandatory_ref');
        $form_can_change_svn_log = $request->get('form_can_change_svn_log');

        $ret = svn_data_update_general_settings($group_id,$form_tracked,$form_preamble,$form_mandatory_ref, $form_can_change_svn_log);
        if ($ret) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_general_settings','upd_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_general_settings','upd_fail'));
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_general_settings','upd_fail'));
    }
}

// Note: no need to purify the output since the svn preamble is stored
// htmlcharized and displayed with the entities.

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_general_settings','gen_settings'),
		      'help' => 'SubversionAdministrationInterface.html#SubversionGeneralSettings'));

$pm = ProjectManager::instance();
$project = $pm->getProject($group_id);
$svn_tracked = $project->isSVNTracked();
$svn_mandatory_ref = $project->isSVNMandatoryRef();
$svn_preamble = $project->getSVNPreamble();
$svn_can_change_log = $project->canChangeSVNLog();

echo '
       <H2>'.$Language->getText('svn_admin_general_settings','gen_settings').'</H2>
       <FORM ACTION="" METHOD="post">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="general_settings">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
       <h3>'.$Language->getText('svn_admin_general_settings','tracking').'</H3><I>
       <p>'.$Language->getText('svn_admin_general_settings','tracking_comment',$GLOBALS['sys_name']).
    '</I>
       <p><b>'.$Language->getText('svn_admin_general_settings','tracking').'</b>&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="form_tracked">
       <OPTION VALUE="1"'.(($svn_tracked == '1') ? ' SELECTED':'').'>'.$Language->getText('global','on').'</OPTION>
       <OPTION VALUE="0"'.(($svn_tracked == '0') ? ' SELECTED':'').'>'.$Language->getText('global','off').'</OPTION>       </SELECT></p>
       <br><h3>'.$Language->getText('svn_admin_general_settings','mandatory_ref').'</H3><I>
       <p>'.$Language->getText('svn_admin_general_settings','mandatory_ref_comment').
    '</I>
       <p><b>'.$Language->getText('svn_admin_general_settings','mandatory_ref').'</b>&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="form_mandatory_ref">
       <OPTION VALUE="1"'.(($svn_mandatory_ref == '1') ? ' SELECTED':'').'>'.$Language->getText('global','on').'</OPTION>
       <OPTION VALUE="0"'.(($svn_mandatory_ref == '0') ? ' SELECTED':'').'>'.$Language->getText('global','off').'</OPTION>       </SELECT></p>
        <br><h3>'.$Language->getText('svn_admin_general_settings','svn_can_change_log').'</H3><I>
       <p>'.$Language->getText('svn_admin_general_settings','svn_can_change_log_comment').
    '</I>
       <p><b>'.$Language->getText('svn_admin_general_settings','svn_can_change_log').'</b>&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="form_can_change_svn_log">
       <OPTION VALUE="1"'.(($svn_can_change_log == '1') ? ' SELECTED':'').'>'.$Language->getText('global','on').'</OPTION>
       <OPTION VALUE="0"'.(($svn_can_change_log == '0') ? ' SELECTED':'').'>'.$Language->getText('global','off').'</OPTION>       </SELECT></p>
       <br>'.$Language->getText('svn_admin_general_settings','preamble',array('/svn/?func=info&group_id='.$group_id,$GLOBALS['sys_name'])).'
       <BR>
       <TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.$svn_preamble.'</TEXTAREA>
        </p>
        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'"></p></FORM>';

svn_footer(array());
?>
