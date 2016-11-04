<?php
// Copyright (c) Enalean, 2015. All Rights Reserved.
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');
require_once('account.php');
require_once('proj_email.php');
require_once('www/admin/admin_utils.php');

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/admin/pending-users.js');

define('ADMIN_APPROVE_PENDING_PAGE_PENDING', 'pending');
define('ADMIN_APPROVE_PENDING_PAGE_VALIDATED', 'validated');

session_require(array('group'=>'1','admin_flags'=>'A'));
$hp = Codendi_HTMLPurifier::instance();
$request = HTTPRequest:: instance();
$action_select = '';
$status= '';
$users_array = array();
if ($request->exist('action_select')) {
    $action_select = $request->get('action_select');
}
if ($request->exist('status')) {
    $status = $request->get('status');
}
if ($request->exist('list_of_users')) {
    $users_array = array_filter(array_map('intval', explode(",", $request->get('list_of_users'))));
}

$valid_page = new Valid_WhiteList('page', array(ADMIN_APPROVE_PENDING_PAGE_PENDING, ADMIN_APPROVE_PENDING_PAGE_VALIDATED));
$page = $request->getValidated('page', $valid_page, '');
$csrf_token = new CSRFSynchronizerToken('/admin/approve_pending_users.php?page=' . $page);
$expiry_date = 0;
    if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && ! preg_match("/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/", $request->get('form_expiry'))) {
        $feedback .= ' '.$Language->getText('admin_approve_pending_users', 'data_not_parsed');
    }else{
        $vDate = new Valid_String();
        if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && $vDate->validate($request->get('form_expiry'))) {
            $date_list = explode("-", $request->get('form_expiry'), 3);
            $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
            $expiry_date = $unix_expiry_time;

        }

        if (($action_select=='activate')) {
            $csrf_token->check();

            $shell="";
            if ($status=='restricted') {
                $newstatus='R';
                $shell=",shell='".$GLOBALS['codendi_bin_prefix'] ."/cvssh-restricted'";
            } else $newstatus='A';

            $users_ids = db_ei_implode($users_array);
            // update the user status flag to active
            db_query("UPDATE user SET expiry_date='".$expiry_date."', status='".$newstatus."'".$shell.
                     ", approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                 " WHERE user_id IN ($users_ids)");

            $em = EventManager::instance();
            foreach ($users_array as $user_id) {
                $em->processEvent('project_admin_activate_user', array('user_id' => $user_id));
            }

            // Now send the user verification emails
            $res_user = db_query("SELECT email, confirm_hash, user_name FROM user "
                     . " WHERE user_id IN ($users_ids)");

             // Send a notification message to the user when account is activated by the Site Administrator
             $base_url = get_server_url();
             while ($row_user = db_fetch_array($res_user)) {
                if (!send_approval_new_user_email($row_user['email'], $row_user['user_name'])) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))
                    );
                }
                usleep(250000);
            }


        } else if($action_select=='validate'){
            $csrf_token->check();
            if($status=='restricted'){
                $newstatus='W';
            }else{
                $newstatus='V';
            }


            // update the user status flag to active
            db_query("UPDATE user SET expiry_date='".$expiry_date."', status='".$newstatus."'".
                     ", approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                     " WHERE user_id IN (".implode(',', $users_array).")");

            // Now send the user verification emails
            $res_user = db_query("SELECT email, confirm_hash, user_name FROM user "
                     . " WHERE user_id IN (".implode(',', $users_array).")");

            while ($row_user = db_fetch_array($res_user)) {
                if (!send_new_user_email($row_user['email'], $row_user['user_name'], $row_user['confirm_hash'])) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))
                        );
                }
                usleep(250000);
            }

        } else if ($action_select=='delete') {
            $csrf_token->check();
            db_query("UPDATE user SET status='D', approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                     " WHERE user_id IN (".implode(',', $users_array).")");
            $em = EventManager::instance();
            foreach ($users_array as $user_id) {
                $em->processEvent('project_admin_delete_user', array('user_id' => $user_id));
            }

        }
    }
//
// No action - First time in this script
// Show the list of pending user waiting for approval
//
if ($page == ADMIN_APPROVE_PENDING_PAGE_PENDING){
    $res = db_query("SELECT * FROM user WHERE status='P'");
    $msg = $Language->getText('admin_approve_pending_users','no_pending_validated');
    if($GLOBALS['sys_user_approval'] == 0) {
        $res = db_query("SELECT * FROM user WHERE status='P' OR status='V' OR status='W'");
        $msg = $Language->getText('admin_approve_pending_users','no_pending');
    }
}else if($page == ADMIN_APPROVE_PENDING_PAGE_VALIDATED){
    $res = db_query("SELECT * FROM user WHERE status='V' OR status='W'");
    $msg = $Language->getText('admin_approve_pending_users','no_validated');
}

$users = array();
while ($row = db_fetch_array($res)) {
    $users[] = new Tuleap\User\Admin\PendingUserPresenter(
        $row['user_id'],
        $row['user_name'],
        $row['realname'],
        $row['email'],
        $row['add_date'],
        $row['register_purpose'],
        $row['expiry_date'],
        $row['status']
    );
}

$title = $GLOBALS['Language']->getText('admin_approve_pending_users', 'title');
if ($page === ADMIN_APPROVE_PENDING_PAGE_VALIDATED) {
    $title = $GLOBALS['Language']->getText('admin_approve_pending_users', 'title_validated');
}

if (count($users) === 0) {
    $siteadmin = new Tuleap\Admin\AdminPageRenderer();
    $siteadmin->renderAPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'no-pending',
        array(
            'title'      => $title,
            'msg'        => $msg,
            'go_back'    => $GLOBALS['Language']->getText('admin_approve_pending_users', 'go_back'),
            'take_break' => $GLOBALS['Language']->getText('admin_approve_pending_users', 'take_break')
        )
    );
} else {
    $siteadmin = new Tuleap\Admin\AdminPageRenderer();
    $siteadmin->renderAPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'pending',
        new Tuleap\User\Admin\PendingUsersCollectionPresenter($title, $users, $page, $csrf_token)
    );
}
