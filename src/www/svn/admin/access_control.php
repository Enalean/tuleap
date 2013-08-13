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

$pm = ProjectManager::instance();
$project=$pm->getProject($group_id);
$gname = $project->getUnixName(false);  // don't return a lower case group name


$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));
if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vAccessFile = new Valid_Text('form_accessfile');
    $vAccessFile->setErrorMessage($Language->getText('svn_admin_access_control','upd_fail'));
    if($request->valid($vAccessFile)) {
        $saf = new SVNAccessFile();
        $form_accessfile = $saf->parseGroupLines($project, $request->get('form_accessfile'), true);
        //store the custom access file in db
        $sql = "UPDATE groups
                SET svn_accessfile = '". db_es($form_accessfile) ."'
                WHERE group_id = ". db_ei($group_id);
        db_query($sql);
        
        $buffer = svn_utils_read_svn_access_file_defaults($gname);
        $buffer .= $form_accessfile;
        $ret = svn_utils_write_svn_access_file($gname,$buffer);
        if ($ret) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_access_control','upd_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_access_control','upd_fail'));
        }
    }
}

$hp =& Codendi_HTMLPurifier::instance();

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_access_control','access_ctrl'),
                        'help' => 'svn.html#subversion-access-control'));

echo '
       <H2>'.$Language->getText('svn_admin_access_control','access_ctrl').'</H2>';

if (svn_utils_svn_repo_exists($gname)) {
    $svn_accessfile = svn_utils_read_svn_access_file($gname);

    echo'
       <FORM ACTION="" METHOD="POST">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="access_control">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <p>'.$Language->getText('svn_admin_access_control','def_policy',$GLOBALS['sys_name']).' 
      <h3>'.$Language->getText('svn_admin_access_control','access_ctrl_file').' '. help_button('svn.html#subversion-access-control').':</h3>
      <p>'.str_replace("\n","<br>",svn_utils_read_svn_access_file_defaults($gname,true)).'
       <TEXTAREA cols="70" rows="20" wrap="virtual" name="form_accessfile">'.$hp->purify($svn_accessfile).'</TEXTAREA>
        </p>
        <p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'"></p></FORM>';

} else {
      echo '<p>'.$Language->getText('svn_admin_access_control','not_created');
}
svn_footer(array());
?>
