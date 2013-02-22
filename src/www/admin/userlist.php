<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/event/EventManager.class.php');
require_once('common/dao/UserDao.class.php');
require_once('common/dao/SessionDao.class.php');
require_once('common/include/CSRFSynchronizerToken.class.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

function show_users_list ($res, $offset, $limit, $user_name_search="") {
    $result = $res['users'];
    $hp = Codendi_HTMLPurifier::instance();
    global $Language;
    echo '<P>'.$Language->getText('admin_userlist','legend').'</P>
          <TABLE class="table table-bordered table-striped table-hover">';
    $odd_even = array('boxitem', 'boxitemalt');
    if ($user_name_search != "") {
        $user_name_param="&user_name_search=$user_name_search";
    } else {
        $user_name_param="";
    }

    echo '<thead>';
    echo "<tr><th>".$Language->getText('include_user_home','login_name')."</th>";
    echo "<th>".$Language->getText('include_user_home','real_name')."</th>";
    echo "<th>Profile</th>\n";
    echo "<th>".$Language->getText('admin_userlist','status')."</th>";
    echo '</thead>';
    
    echo '<tbody>';
    if ($res['numrows'] > 0) {
        foreach ($result as $usr) {
            switch ($usr['status']) {
                case User::STATUS_ACTIVE:
                    $status = $Language->getText('admin_userlist','active');
                    $name   = '<strong>'.$usr['user_name'].'</strong>';
                    break;
                case User::STATUS_RESTRICTED:
                    $status = $Language->getText('admin_userlist','restricted');
                    $name   = '<em>'.$usr['user_name'].'</em>';
                    break;
                case User::STATUS_DELETED:
                    $status = $Language->getText('admin_userlist','deleted');
                    $name   = '<i>'.$usr['user_name'].'</i>';
                    break;
                case User::STATUS_SUSPENDED:
                    $status = $Language->getText('admin_userlist','suspended');
                    $name   = $usr['user_name'];
                    break;
                case User::STATUS_PENDING:
                    $status = $Language->getText('admin_userlist','pending');
                    $name   = '* '.$usr['user_name'];
                    break;
                case User::STATUS_VALIDATED:
                    $status = $Language->getText('admin_userlist','validated');
                    $name   = '(v) '.$usr['user_name'];
                    break;
                case User::STATUS_VALIDATED_RESTRICTED:
                    $status = $Language->getText('admin_userlist','validated_restricted');
                    $name   = '(vr) '.$usr['user_name'];
                    break;
            }
            echo "\n<TR>";
            echo "\n<TD><a href=\"usergroup.php?user_id=".$usr['user_id']."\">".$name."</a></TD>";
            echo "\n<TD>". $hp->purify($usr['realname'], CODENDI_PURIFIER_CONVERT_HTML) ."</TD>";
            echo "\n<TD><A HREF=\"/users/".$usr['user_name']."/\">[DevProfile]</A></TD>";
            echo "\n<TD><span class=\"site_admin_user_status_".$usr['status']."\">&nbsp;</span>".$status."</TD>";
            echo "\n</TR>";
        }
    }
    echo "</tbody></TABLE>";
    echo '<div style="text-align:center">';

    if ($offset > 0) {
        echo  '<a href="?offset='.($offset-$limit).$user_name_param.'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
        echo '&nbsp;';
    }
    echo ($offset + count($result)).'/'.$res['numrows'];
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.($offset+$limit).$user_name_param.'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
    }
    echo '</div>';
}

$dao = new UserDao(CodendiDataAccess::instance());
$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 10;

$vUserNameSearch  = new Valid_String('user_name_search');
$user_name_search = '';
if($request->valid($vUserNameSearch)) {
    if ($request->exist('user_name_search')) {
        $user_name_search = $request->get('user_name_search');
    }
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$group_id = false;
if($request->valid($vGroupId)) {
    if ($request->exist('group_id')) {
        $group_id = $request->get('group_id');
    }
}

if (!$group_id) {
    if (isset($user_name_search) && $user_name_search) {
        $result = $dao->listAllUsers($user_name_search, $offset, $limit);
        if ($result['numrows'] == 1) {
            $row = $result['users']->getRow();
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id='.$row['user_id']);
        }
    } else {
        $user_name_search="";
        $result = $dao->listAllUsers(0, $offset, $limit);
    }
} else {
    $result = $dao->listAllUsersForGroup($group_id, $offset, $limit);
}

/*
 * Show list of users
 */
$HTML->header(array('title'=>$Language->getText('admin_userlist','title')));
echo "<p>";
echo $Language->getText('admin_userlist','user_list').":  ";
if (!$group_id) {
    echo "<strong>".$Language->getText('admin_userlist','all_groups')."</strong>";
    echo '</p>';
    $session_dao = new SessionDao();
    echo '<p>';
    echo '<form action="/admin/sessions.php" method="post">';
    $csrf = new CSRFSynchronizerToken('/admin/sessions.php');
    echo $csrf->fetchHTMLInput();
    echo $Language->getText('admin_userlist','active_sessions', $session_dao->count());
    echo '</form>';
} else {
    $pm = ProjectManager::instance();
    echo "<strong>".$Language->getText('admin_userlist', 'group', array($pm->getProject($group_id)->getPublicName()))."</strong>";
}
echo "</p>";

show_users_list ($result, $offset, $limit, $user_name_search);
$HTML->footer(array());

?>
