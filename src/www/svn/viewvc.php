<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('viewvc_utils.php');
require_once('www/svn/svn_utils.php');

if (user_isloggedin()) {
    $vRoot = new Valid_String('root');
    $vRoot->required();
    if(!$request->valid($vRoot)) {
        exit_no_group();
    }
    $root = $request->get('root');
    $group_id = group_getid_by_name($root);
    if($group_id === false) {
        exit_no_group();
    }
    $pm = ProjectManager::instance();
    $project=$pm->getProject($group_id);
    $hp = Codendi_HTMLPurifier::instance();
    $request->valid(new Valid_String('post_changes'));
    $request->valid(new Valid_String('SUBMIT'));
    if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
        $vML = new Valid_Email('form_mailing_list', ',');
        $vHeader = new Valid_String('form_mailing_header');
        if($request->valid($vML)) {
            if($request->valid($vHeader)) {
                $form_mailing_list = $request->get('form_mailing_list');
                $form_mailing_header = $request->get('form_mailing_header');
                if ($project->setSVNMailingListAndHeader($form_mailing_list,$form_mailing_header)) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_notification','upd_success'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_email_fail'));
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_notification','upd_fail'));
        }
    }

    $vRootType = new Valid_WhiteList('roottype', array('svn'));
    $vRootType->setErrorMessage($Language->getText('svn_viewvc','bad_roottype'));
    $vRootType->required();
    if($request->valid($vRootType)) {
        if (!svn_utils_check_access(user_getname(), $root, viewvc_utils_getfile("/svn/viewvc.php"))) {
            exit_error($Language->getText('svn_viewvc','access_denied'),
            $Language->getText('svn_viewvc','acc_den_comment',session_make_url("/project/memberlist.php?group_id=$group_id")));
        }
        viewvc_utils_track_browsing($group_id,'svn');

        $display_header_footer = viewvc_utils_display_header();

        if ($display_header_footer) {
            $prefix_title = '';
            if ($path = viewvc_utils_getfile("/svn/viewvc.php")) {
                $prefix_title = basename($path) .' - ';
            }
            $GLOBALS['HTML']->addStylesheet('/viewvc-static/styles.css');
            svn_header(array(
        'title'      => $prefix_title . $Language->getText('svn_utils','browse_tree')
            ));
        }

        viewvc_utils_passcommand();
        // Must be at least Project Admin to configure this
        if (user_ismember($group_id,'A')  || user_ismember($group_id,'SVN_ADMIN')) {
            //TODO: Should be retreived correctly
            $mailingList = $project->getSVNMailingList();
            $mailingHeader = $project->getSVNMailingHeader();

            $path = '/'.viewvc_utils_getfile("/svn/viewvc.php");
            echo '
       <FORM ACTION="" METHOD="post">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="path" VALUE="'.$path.'">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
       '.$Language->getText('svn_admin_notification','mail_comment').'

       <P><b>'.$Language->getText('svn_admin_notification','mail_to').'</b></p><p><INPUT TYPE="TEXT" SIZE="70" NAME="form_mailing_list" VALUE="'.$hp->purify($mailingList).'"></p>

       <p><b>'.$Language->getText('svn_admin_notification','header').'</b></p>
       <p><INPUT TYPE="TEXT" SIZE="20" NAME="form_mailing_header" VALUE="'.$hp->purify($mailingHeader).'"></p>

        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'"></p></FORM>';
        }
        if ($display_header_footer) {
            site_footer(array());
        }
    } else {
        svn_header(array ('title'=>$Language->getText('svn_utils','browse_tree')));

        site_footer(array());
    }
} else {
    exit_not_logged_in();
}
?>
