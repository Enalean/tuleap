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

/**
*   select the fields sort order and the header arrow direction
*
* @param String $previous_sort_header
* @param String $current_sort_header
* @param String $sort_order
* @param Integer $offset
*
* @return Array
*/
function get_sort_values ($previous_sort_header, $current_sort_header, $sort_order, $offset){
    $sort_order_hash = array(
        'sort_header' => $current_sort_header,
        'user_name_icon' => '',
        'realname_icon' => '',
        'status_icon' => '',
        'order' => 'ASC',
        );
    $sort_order_hash[$current_sort_header."_icon"]="icon-caret-up";

    if ($offset === 0) {
        if ($previous_sort_header === $current_sort_header) {
            if ($sort_order === "ASC") {
                $sort_order_hash[$current_sort_header."_icon"] = "icon-caret-down";
                $sort_order_hash["order"] = "DESC";
            }
            else {
                $sort_order_hash[$current_sort_header."_icon"] = "icon-caret-up";
                $sort_order_hash["order"] = "ASC";
            }
        }
    }
    else {
        if ($sort_order === "ASC") {
            $sort_order_hash[$current_sort_header."_icon"] = "icon-caret-up";
            $sort_order_hash["order"] = "ASC";
        }
        else {
            $sort_order_hash[$current_sort_header."_icon"]="icon-caret-down";
            $sort_order_hash["order"] = "DESC";
        }
    }
    return $sort_order_hash;
}

function tooltip_values($nb_member_of, $nb_admin_of, $Language) {
    if ($nb_member_of) {
        $tooltip_values = array(
            'tooltip' => $Language->getText('admin_userlist', 'member_of', $nb_member_of),
            'content' => $nb_member_of,
        );

        if ($nb_admin_of) {
            $tooltip_values['tooltip'] .= '<br>'. $Language->getText('admin_userlist', 'admin_of', $nb_admin_of);
            $tooltip_values['content'] .= ' ('.$nb_admin_of.')';
        }
    } else {
        $tooltip_values = array(
            'tooltip' => $Language->getText('admin_userlist','not_member_of'),
            'content' => '-',
        );
    }

    return $tooltip_values;
}

function getSelectedFromStatus($status, $status_values) {
    if(in_array($status, $status_values)) {
        return "selected";
    }
}

if ($request->exist('export')) {
    //Validate user_name_search
    $vUserNameSearch  = new Valid_String('user_name_search');
    $user_name_search = '';
    if($request->valid($vUserNameSearch)) {
        if ($request->exist('user_name_search')) {
            $user_name_search = $request->get('user_name_search');
        }
    }
    //Get current sort header
    $header_whitelist = array('user_name', 'realname', 'status');
    if (in_array($request->get('current_sort_header'), $header_whitelist)) {
        $current_sort_header=$request->get('current_sort_header');
    }
    else {
        $current_sort_header = 'user_name';
    }
    //Get current sort order
    $sort_order_whitelist = array('ASC','DESC');
    if (in_array($request->get('sort_order'), $sort_order_whitelist)) {
        $sort_order=$request->get('sort_order');
    }
    else {
        $sort_order = 'ASC';
    }
    //Get status values
    $status_values = array();
    if ($request->exist('status_values')) {
        $status = $request->get('status_values');
        if($status != "" && $status != "ANY") {
            $status_values = explode(',', $status);
        }
    }
    //export user list in csv format
    $user_list_exporter = new Admin_UserListExporter();
    $user_list_exporter->exportUserList($user_name_search, $current_sort_header, $sort_order, $status_values);
    exit;
}

function show_users_list ($res, $offset, $limit, $user_name_search="", $sort_params, $status_values, $group_id) {
    $result = $res['users'];
    $hp = Codendi_HTMLPurifier::instance();
    global $Language;
    echo '<P>'.$Language->getText('admin_userlist','legend').'</P>
          <TABLE class="table table-bordered table-striped table-hover">';
    $user_status = implode(',', $status_values);
    if ($user_status == "") {
        $user_status = "ANY";
    }
    echo '<form action="/admin/userlist.php?user_name_search='.$hp->purify($user_name_search).'&export&current_sort_header='.$hp->purify($sort_params["sort_header"]).'&sort_order='.$hp->purify($sort_params["order"]).'&status_values='.$hp->purify($user_status).'" method="post">';
        echo'<input type="submit" class="btn tlp-button-secondary" name="exp-csv" value="Export CSV">';
    echo '</form>';

    $odd_even = array('boxitem', 'boxitemalt');
    if ($user_name_search != "") {
        $user_name_param="&user_name_search=$user_name_search";
    } else {
        $user_name_param="";
    }
    echo '<thead>';
    echo "<tr><th><a class='table_header_sort' href=\"userlist.php?previous_sort_header=".$sort_params["sort_header"]."&current_sort_header=user_name&user_name_search=".$hp->purify($user_name_search)."&sort_order=".$sort_params["order"]."&status_values=".$hp->purify($user_status)."\">".$Language->getText('include_user_home','login_name')." <span class=\"pull-right ".$sort_params["user_name_icon"]."\"></span></a></th>";
    echo "<th><a class='table_header_sort' href=\"userlist.php?previous_sort_header=".$sort_params["sort_header"]."&current_sort_header=realname&user_name_search=".$hp->purify($user_name_search)."&sort_order=".$sort_params["order"]."&status_values=".$hp->purify($user_status)."\">".$Language->getText('include_user_home','real_name')." <span class=\"pull-right ".$sort_params["realname_icon"]."\"></span></a></th>";
    echo "<th>Profile</th>\n";
    if(!$group_id) {
        echo "<th>".$hp->purify($Language->getText('admin_userlist','nb_projects'))."</th>";
    }
    echo "<th><a class='table_header_sort' href=\"userlist.php?previous_sort_header=".$sort_params["sort_header"]."&current_sort_header=status&user_name_search=".$hp->purify($user_name_search)."&sort_order=".$sort_params["order"]."&status_values=".$hp->purify($user_status)."\">".$Language->getText('admin_userlist','status')." <span class=\"pull-right ".$sort_params["status_icon"]."\"></span></a></th>";
    echo '</thead>';
    
    echo '<tbody>';
    if ($res['numrows'] > 0) {
        foreach ($result as $usr) {
            if(!$group_id) {
                $tooltip_values = tooltip_values($usr['member_of'], $usr['admin_of'], $Language);
            }
            switch ($usr['status']) {
                case PFUser::STATUS_ACTIVE:
                    $status = $Language->getText('admin_userlist','active');
                    $name   = '<strong>'.$usr['user_name'].'</strong>';
                    break;
                case PFUser::STATUS_RESTRICTED:
                    $status = $Language->getText('admin_userlist','restricted');
                    $name   = '<em>'.$usr['user_name'].'</em>';
                    break;
                case PFUser::STATUS_DELETED:
                    $status = $Language->getText('admin_userlist','deleted');
                    $name   = '<i>'.$usr['user_name'].'</i>';
                    break;
                case PFUser::STATUS_SUSPENDED:
                    $status = $Language->getText('admin_userlist','suspended');
                    $name   = $usr['user_name'];
                    break;
                case PFUser::STATUS_PENDING:
                    $status = $Language->getText('admin_userlist','pending');
                    $name   = '* '.$usr['user_name'];
                    break;
                case PFUser::STATUS_VALIDATED:
                    $status = $Language->getText('admin_userlist','validated');
                    $name   = '(v) '.$usr['user_name'];
                    break;
                case PFUser::STATUS_VALIDATED_RESTRICTED:
                    $status = $Language->getText('admin_userlist','validated_restricted');
                    $name   = '(vr) '.$usr['user_name'];
                    break;
            }
            echo "\n<TR>";
            echo "\n<TD><a href=\"usergroup.php?user_id=".$usr['user_id']."\">".$name."</a></TD>";
            echo "\n<TD>". $hp->purify($usr['realname'], CODENDI_PURIFIER_CONVERT_HTML) ."</TD>";
            echo "\n<TD><A HREF=\"/users/".$usr['user_name']."/\">[DevProfile]</A></TD>";
            if(!$group_id) {
                echo "<TD class='tooltip_selector' data-toggle='tooltip' data-placement='top' data-original-title='".$hp->purify($tooltip_values['tooltip'])."'>".$hp->purify($tooltip_values['content'])."</TD>";
            }
            echo "\n<TD><span class=\"site_admin_user_status_".$usr['status']."\">&nbsp;</span>".$status."</TD>";
            echo "\n</TR>";
        }
    }
    echo "</tbody></TABLE>";
    echo '<div style="text-align:center">';
    if ($offset > 0) {
        echo  '<a href="?offset='.($offset-$limit).$user_name_param.'&current_sort_header='.$sort_params["sort_header"].'&user_name_search='.$user_name_search.'&sort_order='.$sort_params["order"].'&status_values='.$hp->purify($user_status).'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
        echo '&nbsp;';
    }
    echo ($offset + count($result)).'/'.$res['numrows'];
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.($offset+$limit).$user_name_param.'&current_sort_header='.$sort_params["sort_header"].'&user_name_search='.$user_name_search.'&sort_order='.$sort_params["order"].'&status_values='.$hp->purify($user_status).'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
    }
    echo '</div>';
}

$dao = new UserDao(CodendiDataAccess::instance());
$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 100;

$vUserNameSearch  = new Valid_String('user_name_search');
$user_name_search = '';
if($request->valid($vUserNameSearch)) {
    if ($request->exist('user_name_search')) {
        $user_name_search = $request->get('user_name_search');
    }
}

$header_whitelist = array('user_name', 'realname', 'status');
if (in_array($request->get('previous_sort_header'), $header_whitelist)) {
    $previous_sort_header=$request->get('previous_sort_header');
}
else {
    $previous_sort_header = '';
}
if (in_array($request->get('current_sort_header'), $header_whitelist)) {
    $current_sort_header=$request->get('current_sort_header');
}
else {
    $current_sort_header = 'user_name';
}

$sort_order_whitelist = array('ASC','DESC');
if (in_array($request->get('sort_order'), $sort_order_whitelist)) {
    $sort_order=$request->get('sort_order');
}
else {
    $sort_order = 'ASC';
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$group_id = false;
if($request->valid($vGroupId)) {
    if ($request->exist('group_id')) {
        $group_id = $request->get('group_id');
    }
}

$sort_params = get_sort_values($previous_sort_header, $current_sort_header, $sort_order, $offset);
$status_values = array();
$anySelect     = "selected";
if ($request->exist('status_values')) {
    $status_values = $request->get('status_values');
    if(! is_array($status_values)) {
        $status_values = explode(",", $status_values);
    }
    if(in_array('ANY', $status_values)) {
        $status_values = array();
    } else {
        $anySelect = "";
    }
}

if (!$group_id) {
    if (isset($user_name_search) && $user_name_search) {
        $result = $dao->listAllUsers($user_name_search, $offset, $limit, $sort_params['sort_header'], $sort_params['order'], $status_values);
        if ($result['numrows'] == 1) {
            $row = $result['users']->getRow();
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id='.$row['user_id']);
        }
    } else {
        $user_name_search = "";
        $result           = $dao->listAllUsers(0, $offset, $limit, $sort_params['sort_header'], $sort_params['order'], $status_values);
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
/*
 * Add search field
 */
$hp = Codendi_HTMLPurifier::instance();
$user_name_search_purify = $hp->purify($user_name_search);
$search_purify = $hp->purify($Language->getText('admin_main', 'search'));
echo '<form name="usersrch" action="userlist.php" method="get" class="form-horizontal">
       <table>
        <tr>
         <td valign=top>
           <label> <strong>'.$Language->getText("admin_userlist","status").'</strong> </label>
             <select multiple name="status_values[]" size=8>
               <option value="ANY" '.$anySelect.'>Any</option>
               <option value="'.PFUser::STATUS_ACTIVE.'" '.getSelectedFromStatus(PFUser::STATUS_ACTIVE, $status_values).'>'.$Language->getText("admin_userlist","active").'</option>
               <option value="'.PFUser::STATUS_RESTRICTED.'" '.getSelectedFromStatus(PFUser::STATUS_RESTRICTED, $status_values).'>'.$Language->getText("admin_userlist","restricted").'</option>
               <option value="'.PFUser::STATUS_DELETED.'" '.getSelectedFromStatus(PFUser::STATUS_DELETED, $status_values).'>'.$Language->getText("admin_userlist","deleted").'</option>
               <option value="'.PFUser::STATUS_SUSPENDED.'" '.getSelectedFromStatus(PFUser::STATUS_SUSPENDED, $status_values).'>'.$Language->getText("admin_userlist","suspended").'</option>
               <option value="'.PFUser::STATUS_PENDING.'" '.getSelectedFromStatus(PFUser::STATUS_PENDING, $status_values).'>'.$Language->getText("admin_userlist","pending").'</option>
               <option value="'.PFUser::STATUS_VALIDATED.'" '.getSelectedFromStatus(PFUser::STATUS_VALIDATED, $status_values).'>'.$Language->getText("admin_userlist","validated").'</option>
               <option value="'.PFUser::STATUS_VALIDATED_RESTRICTED.'" '.getSelectedFromStatus(PFUser::STATUS_VALIDATED_RESTRICTED, $status_values).'>'.$Language->getText("admin_userlist","validated_restricted").'</option>
             </select>
         </td>
         <td valign=top>
           <p>
             <label> <strong>'.$Language->getText('admin_main', 'search_user').'</strong> </label>
           </p>
           <input type="text" name="user_name_search" class="user_name_search" placeholder="'.$search_purify.'" value="'.$user_name_search_purify.'" />
         </td>
        </tr>
       </table>
       <div align="center">
         <button type="submit" class="btn btn-primary tlp-button-primary">'.$search_purify.'
           <i class="icon-search"></i>
         </button>
       </div>
      </form>';
echo "</p>";
show_users_list ($result, $offset, $limit, $user_name_search, $sort_params, $status_values, $group_id);
echo '<script type="text/javascript" src="/scripts/tuleap/userlist.js"></script>';
$HTML->footer(array());
?>
