<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Terray 2008, Codendi Team, Xerox
//

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');


$request =& HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);

//Only project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);

if ($ugroup_id) {
    $uGroup = new UGroup(array('ugroup_id' => $ugroup_id));
    $ugroupUpdateUsersAllowed = !$uGroup->isBound();
    $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroup_id, 'allowed' => &$ugroupUpdateUsersAllowed));
    if ($ugroupUpdateUsersAllowed) {
        $res = ugroup_db_get_ugroup($ugroup_id);
        if ($res) {
            $user_id = $request->getValidated('user_id', 'uint', 0);
            if ($user_id) {
                ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
            }
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='. $group_id .'&ugroup_id='. $ugroup_id .'&func=edit&pane=members');
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editugroup','ug_not_found',array($ugroup_id,db_error())));
            $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'operation_not_allowed'));
        $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='. $group_id .'&ugroup_id='. $ugroup_id .'&func=edit&pane=members');
    }
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
    $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $group_id);
}

?>