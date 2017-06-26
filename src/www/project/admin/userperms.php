<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright (c) Enalean, 2015. All Rights Reserved.
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/user/UserHelper.class.php');

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\FRSPermissionFactory;

//
//  get the Group object
//
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
}
$atf = new ArtifactTypeFactory($group);
if (!$group || !is_object($group) || $group->isError()) {
	exit_error($Language->getText('global','error'),$Language->getText('project_admin_index','not_get_atf'));
}
// Get the artfact type list
$at_arr = $atf->getArtifactTypes();

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$project=$pm->getProject($group_id);
if ($project->isError()) {
        //wasn't found or some other problem
        echo $Language->getText('project_admin_userperms','unable_load_p')."<br>";
    	return;
}

$should_hide_warning_for_frs_legacy_perms = 'hide-warning-frs-legacy-perms';

$csrf_hide_warning = new CSRFSynchronizerToken('/project/admin/userperms.php?group_id='. (int)$group_id);
if ($request->get($should_hide_warning_for_frs_legacy_perms)) {
    $csrf_hide_warning->check();
    $current_user->setPreference($should_hide_warning_for_frs_legacy_perms, true);
}

// ########################### form submission, make updates
if ($request->exist('submit')) {
    group_add_history ('changed_member_perm','',$group_id);
    $nb_errors = 0;

    $res_dev = db_query("SELECT * FROM user_group WHERE group_id=$group_id");
    while ($row_dev = db_fetch_array($res_dev)) {

        if($request ->exist("admin_user_$row_dev[user_id]")){
            $forum_flags= "forums_user_$row_dev[user_id]";
            $file_flags = "file_user_$row_dev[user_id]";
            $wiki_flags = "wiki_user_$row_dev[user_id]";
            $svn_flags  = "svn_user_$row_dev[user_id]";
            $news_flags = "news_user_$row_dev[user_id]";

            $flags = array(
                'forum_flags',
                'file_flags',
                'wiki_flags',
                'news_flags',
                'svn_flags'
            );
            $admin_flags  = "admin_user_$row_dev[user_id]";
            $$admin_flags = $request->get($admin_flags);
        //
        // cannot turn off their own admin flag if no other admin in project -- set it back to 'A'
        //
            if (user_getid() == $row_dev['user_id']) {
                if ($$admin_flags != 'A') {
                    $other_admin_exists=false;
                    // Check that there is still at least one admin
                    $sql = "SELECT NULL FROM user_group WHERE user_id != ".db_ei($row_dev['user_id'])." AND admin_flags='A' AND group_id=".db_ei($group_id).' LIMIT 1';
                    $res_dev2 = db_query($sql);

                    if (db_numrows($res_dev2) > 0 ) {
                        $other_admin_exists=true;
                    }

                    if (!$other_admin_exists) {
                        $is_admin_only_modification = true;
                        foreach ($flags as $flag) {
                            if ($request->get($$flag) !== false && $request->get($$flag) != $row_dev[$flag]) {
                                $is_admin_only_modification = false;
                            }
                        }
                        if ($is_admin_only_modification) {
                            $nb_errors++;
                        }
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_userperms','cannot_remove_admin_stat'));
                        $$admin_flags='A';
                    }
                }
            }

            $sql = "UPDATE user_group SET admin_flags='". db_es($$admin_flags) ."'";
            foreach ($flags as $flag) {
                if ($request->exist($$flag)) {
                    $sql .= ", $flag = '". db_es($request->get($$flag)) ."'";
                }
            }
            $sql .= " WHERE user_id='$row_dev[user_id]' AND group_id='$group_id'";

            $res = db_query($sql);
            $tracker_error = false;
            if ( $project->usesTracker()&&$at_arr ) {
                for ($j = 0; $j < count($at_arr); $j++) {
                    $atid = $at_arr[$j]->getID();
                    $perm_level = "tracker_user_$row_dev[user_id]_$atid";
                     //echo "Tracker ".$at_arr[$j]->getName()."(".$at_arr[$j]->getID()."): ".$perm_level."=".$$perm_level."<br>";
                    if ( $at_arr[$j]->existUser($row_dev['user_id']) ) {
                        if ( !$at_arr[$j]->updateUser($row_dev['user_id'],$$perm_level) ) {
                            echo $at_arr[$j]->getErrorMessage();
                            $tracker_error = true;
                        }
                    } else {
                        if ( !$at_arr[$j]->addUser($row_dev['user_id'],$$perm_level) ) {
                            $tracker_error = true;
                        }
                    }

                }
            }

            if (!$res || $tracker_error) {
                $nb_errors++;
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_userperms','perm_fail_for',$row_dev['user_id']).' '.db_error());
            }

            // Raise an event
            $em =& EventManager::instance();
            $user_permissions = array();
            $user_permissions['admin_flags'] = $$admin_flags;
            foreach ($flags as $flag) {
                if (isset($$$flag)) {
                    $user_permissions[$flag] = $$$flag;
                }
            }
            $em->processEvent('project_admin_change_user_permissions', array(
                'group_id' => $group_id,
                'user_id' => $row_dev['user_id'],
                'user_permissions' => $user_permissions,
                'previous_permissions' => $row_dev,
            ));
        }
	}

        if (count($row_dev) > $nb_errors) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_userperms','perm_upd'));
        }
}

$vPattern = new Valid_String('search');
$vPattern->required();
if($request->valid($vPattern)) {
    $pattern = $request->get('search');
} else {
	$pattern = '';
}

$offset = $request->getValidated('offset', 'uint', 0);
if (!$offset) {
    $offset = 0;
}
$number_per_page = 25;

$sql = array();
$sql['select'] = "SELECT SQL_CALC_FOUND_ROWS user.user_name AS user_name,
                  user.realname AS realname,
                  user.user_id AS user_id,
                  user_group.admin_flags,
                  user_group.bug_flags,
                  user_group.forum_flags,
                  user_group.project_flags,
                  user_group.patch_flags,
                  user_group.file_flags,
                  user_group.support_flags,
                  user_group.wiki_flags,
                  user_group.svn_flags,
                  user_group.news_flags";
$sql['from']  = " FROM user,user_group ";
$sql['where'] = " WHERE user.user_id = user_group.user_id
                    AND user_group.group_id = ". db_ei($group_id);

if ($request->exist('search') && $request->get('search') != null) {
    $uh = UserHelper::instance();
    $sql['filter'] = $uh->getUserFilter($search);
} else {
    $sql['filter'] = '';

}

$sql['order'] = " ORDER BY user.user_name ";
$sql['limit'] = " LIMIT ". db_ei($offset) .", ". db_ei($number_per_page);

if ($project->usesTracker()&&$at_arr ) {
    for ($j = 0; $j < count($at_arr); $j++) {
        $atid = db_ei($at_arr[$j]->getID());
        $sql['select'] .= ", IFNULL(artifact_perm_". $atid .".perm_level, 0) AS perm_level_". $atid ." ";
        $sql['from']   .= " LEFT JOIN artifact_perm AS artifact_perm_". $atid ."
                                 ON(artifact_perm_". $atid .".user_id = user_group.user_id
                                    AND artifact_perm_". $atid .".group_artifact_id = ". $atid .") ";
    }
}
$res_dev = db_query($sql['select'] . $sql['from'] . $sql['where'] . $sql['filter'] . $sql['order'] . $sql['limit']);

if (!$res_dev || db_numrows($res_dev)==0 || $number_per_page < 1) {
    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms','no_users_found'));
}
$sql = 'SELECT FOUND_ROWS() AS nb';
$res = db_query($sql);
$row = db_fetch_array($res);
$num_total_rows = $row['nb'];

$sql = "SELECT ugroup_user.user_id AS user_id, ugroup.ugroup_id AS ugroup_id, ugroup.name AS name
FROM ugroup, ugroup_user
WHERE ugroup.group_id = ". db_ei($group_id) ."
  AND ugroup_user.ugroup_id = ugroup.ugroup_id";
$res_ugrp = db_query($sql);
$ugroups = array();
while($row = db_fetch_array($res_ugrp)) {
    $ugroups[$row['user_id']][] = $row;
}

project_admin_header(array('title'=>$Language->getText('project_admin_utils','user_perms'),'group'=>$group_id,
		     'help' => 'project-admin.html#user-permissions'));

echo '
<h2>'.$Language->getText('project_admin_utils','user_perms').'</h2>';
echo '<FORM action="userperms.php" name = "form_search" method="post" class="form-inline">';

echo $Language->getText('project_admin_utils','search_user');
echo '&nbsp;';
echo '<div class="input-append">';
echo '<INPUT type="text" name="search" value="'.$pattern.'" id="search_user">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">';
$js = "new UserAutoCompleter('search_user',
                          '".util_get_dir_image_theme()."',
                          true);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

echo '<INPUT class="btn" type="submit" name ="searchUser" value="'.$Language->getText('admin_main', 'search').'"></div>';
echo '</FORM>';

$frs_permission_manager = new FRSPermissionManager(
    new FRSPermissionDao(),
    new FRSPermissionFactory(new FRSPermissionDao())
);

if ($frs_permission_manager->doesProjectHaveOldFrsAdminMembers($group)
    && ! $current_user->getPreference($should_hide_warning_for_frs_legacy_perms)
) {
    echo '<form action="" method="POST">
        <input type="hidden" name="group_id" value="'. (int)$group_id .'" />
        <input type="hidden" name="'. $should_hide_warning_for_frs_legacy_perms .'" value="1" />
        '. $csrf_hide_warning->fetchHTMLInput() .'
        <div class="alert alert-warning">
            <button type="submit" class="close" aria-hidden="true">&times;</button> </a>
            <i class="icon-warning-sign"></i> '.
            $GLOBALS['Language']->getText('file_admin_index', 'warning_new_permission', (int)$group_id).
        '</div>
        </form>';
}

if ($res_dev && db_numrows($res_dev) > 0 && $number_per_page > 0) {

echo '<FORM action="userperms.php" name= "form_update" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<INPUT type="hidden" name="offset" value="'.$offset.'">';

echo '<TABLE class="table">';

$head = '<thead><tr>';
$i = 0;

function userperms_add_header($header) {
    global $i, $head, $Language;
    if ($i++ % 10 == 0) {
        $head .= '<th>'.$Language->getText('project_admin_userperms','user_name').'</th>';
    }
    $head .= $header;
}

$head .= '<th>'.$Language->getText('project_admin_userperms','user_name').'</th>';
$head .= '<th>'.$Language->getText('project_admin_userperms','proj_admin').'</th>';

if ($project->usesCVS()) {
    $head .= '<th>'.$Language->getText('project_admin_userperms','cvs_write').'</th>';
}
if ($project->usesSVN()) {
    $head .= '<th>'.$Language->getText('project_admin_userperms','svn').'</th>';
}
if ($project->usesForum()) {
    $head .= '<th>'.$Language->getText('project_admin_userperms','forums').'</th>';
}
if ($project->usesWiki()) {
    $head .= '<th>'.$Language->getText('project_admin_userperms','wiki').'</th>';
}
if ($project->usesNews()) {
    $head .= '<th>'.$Language->getText('project_admin_userperms','news').'</th>';
}

if ( $project->usesTracker()&&$at_arr ) {
	for ($j = 0; $j < count($at_arr); $j++) {
        userperms_add_header('<th>'.$Language->getText('project_admin_userperms','tracker',$at_arr[$j]->getName()).'</th>');
	}
}

$head .= '<th>'.$Language->getText('project_admin_userperms','member_ug').'</th>';

$head .= '</tr></thead><tbody>';

echo $head;

    $i=0;
    function userperms_add_cell($user_name, $cell) {
        global $k;
        if ($k++ % 10 == 0) {
            echo '<td>'. $user_name .'</td>';
        }
        echo $cell;
    }

    $uh = new UserHelper();
    $hp = Codendi_HTMLPurifier::instance();

    while ($row_dev = db_fetch_array($res_dev)) {
        $i++;
        print '<TR class="'. util_get_alt_row_color($i) .'">';
        $user_name = $hp->purify($uh->getDisplayName($row_dev['user_name'], $row_dev['realname']), CODENDI_PURIFIER_CONVERT_HTML);
        echo '<td><a name="'. ucfirst(substr($row_dev['user_name'], 0, 1)) .'"></a>'. $user_name .'</td>';
        echo '
            <TD>
            <INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="A" '.(($row_dev['admin_flags']=='A')?'CHECKED':'').'>&nbsp;'.$Language->getText('global','yes').'<BR>
            <INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="" '.(($row_dev['admin_flags']=='')?'CHECKED':'').'>&nbsp;'.$Language->getText('global','no').'
            </TD>';
        if ($project->usesCVS()) {
            echo '<TD>'.$Language->getText('global','yes').'</TD>';
        }
     // svn
        if ($project->usesSVN()) {
            $cell = '';
            $cell .= '<TD><SELECT name="svn_user_'.$row_dev['user_id'].'">';
            $cell .= '<OPTION value="0"'.(($row_dev['svn_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            $cell .= '<OPTION value="2"'.(($row_dev['svn_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_index','admin');
            $cell .= '</SELECT></TD>';
            echo $cell;
        }

        // forums
        if ($project->usesForum()) {
            $cell = '';
            $cell .= '<TD><SELECT name="forums_user_'.$row_dev['user_id'].'">';
            $cell .= '<OPTION value="0"'.(($row_dev['forum_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            $cell .= '<OPTION value="2"'.(($row_dev['forum_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','moderator');
            $cell .= '</SELECT></TD>';
            echo $cell;
        }
       // wiki
        if ($project->usesWiki()) {
            $cell = '';
            $cell .= '<TD><SELECT name="wiki_user_'.$row_dev['user_id'].'">';
            $cell .= '<OPTION value="0"'.(($row_dev['wiki_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            $cell .= '<OPTION value="2"'.(($row_dev['wiki_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_index','admin');
            $cell .= '</SELECT></TD>';
            echo $cell;
        }

        // News
        if ($project->usesNews()) {
            $cell = '';
            $cell .= '<TD><SELECT name="news_user_'.$row_dev['user_id'].'">';
            $cell .= '<OPTION value="0"'.(($row_dev['news_flags']==0)?" selected":"").'>'.$Language->getText('project_admin_userperms','read_perms');
            $cell .= '<OPTION value="1"'.(($row_dev['news_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','write_perms');
            $cell .= '<OPTION value="2"'.(($row_dev['news_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_index','admin');
            $cell .= '</SELECT></TD>';
            echo $cell;
        }

        $k = 0;
        if ( $project->usesTracker()&&$at_arr ) {
            // Loop on tracker
            for ($j = 0; $j < count($at_arr); $j++) {
                $atid = $at_arr[$j]->getID();
                $perm = $row_dev['perm_level_' . $atid];
                $cell = '';
                $cell .= '<TD><SELECT name="tracker_user_'.$row_dev['user_id'].'_'.$atid.'">';
                $cell .= '<OPTION value="0"'.(($perm==0)?" selected":"").'>'.$Language->getText('global','none');
                $cell .= '<OPTION value="3"'.(($perm==3 || $perm==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin');
                $cell .= '</SELECT></TD>';
                userperms_add_cell($user_name, $cell);
            }
        }

        print '<TD>';
        if (isset($ugroups[$row_dev['user_id']])) {
            $is_first=true;
            foreach($ugroups[$row_dev['user_id']] as $row) {
                if (!$is_first) {
                    print ', ';
                }
                print '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=edit">'.
                    $row['name'].'</a>';
                $is_first = false;
            }
        } else {
            print '-';
        }
        print '</TD>';

        print '</TR>';
        if ($i % 10 == 0) {
            echo $head;
        }
    } // while



echo '</tbody>
    </table>';
if ($num_total_rows && $number_per_page < $num_total_rows) {
    //Jump to page
    $nb_of_pages = ceil($num_total_rows / $number_per_page);
    $current_page = round($offset / $number_per_page);
    if (isset($pattern) && $pattern != '') {
    	$search = '&amp;search='.$pattern;
    } else {
    	$search = '';

    }
    echo '<div style="font-family:Verdana">Page: ';
    $width = 10;
    for ($i = 0 ; $i < $nb_of_pages ; ++$i) {
        if ($i == 0 || $i == $nb_of_pages - 1 || ($current_page - $width / 2 <= $i && $i <= $width / 2 + $current_page)) {
            echo '<a href="?'.
                'group_id='. (int)$group_id .
                '&amp;offset='. (int)($i * $number_per_page) .
                $search.
                '">';
            if ($i == $current_page) {
                echo '<b>'. ($i + 1) .'</b>';
            } else {
                echo $i + 1;
            }
            echo '</a>&nbsp;';
        } else if ($current_page - $width / 2 - 1 == $i || $current_page + $width / 2 + 1 == $i) {
            echo '...&nbsp;';
        }
    }
    echo '</div>';
}

echo '<P align="center"><INPUT type="submit" name="submit" value="'.$Language->getText('project_admin_userperms','upd_user_perm').'">
</FORM>';
}

project_admin_footer(array());
?>
