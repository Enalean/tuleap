<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * http://sourceforge.net
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('account.php');
require_once('common/include/TemplateSingleton.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');

$request = HTTPRequest::instance();

// Valid group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if(!$request->valid($vGroupId)) {
    exit_error($Language->getText('project_admin_index','invalid_p'), $Language->getText('project_admin_index','p_not_found'));
}
$group_id = $request->get('group_id');

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

//
//  get the Group object
//
$pm    = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
  exit_no_group();
}

//if the project isn't active, require you to be a member of the super-admin group
if ($group->getStatus() != 'A') {
    $request->checkUserIsSuperUser();
}

$em = EventManager::instance();

$vFunc = new Valid_WhiteList('func', array('adduser', 'rmuser', 'change_group_type', 'member_req_notif_group', 'member_req_notif_message'));
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    /*
      updating the database
    */
    switch ($request->get('func')) {
    case 'adduser':
        // add user to this project
        $form_unix_name = $request->get('form_unix_name');
        $res = account_add_user_to_group ($group_id, $form_unix_name);
        break;

    case 'rmuser':
        // remove a user from this portal
        $rm_id        = $request->getValidated('rm_id', 'uint', 0);
        $user_remover = new \Tuleap\Project\UserRemover(
            ProjectManager::instance(),
            EventManager::instance(),
            new ArtifactTypeFactory(false),
            new \Tuleap\Project\UserRemoverDao()
        );
        $user_remover->removeUserFromProject($group_id, $rm_id);
        break;

    case 'change_group_type':
        $form_project_type = $request->getValidated('form_project_type', 'uint', 0);
        if (user_is_super_user() && ($group->getType() != $form_project_type)) {
            group_add_history ('group_type',$group->getType(),$group_id);

            $template = TemplateSingleton::instance();
            $group->setType($form_project_type);

            //set also flag on trackers to be copied or not on project instanciation
            if ($template->isTemplate($form_project_type)) {
                db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='1' WHERE group_id='$group_id'");
            } else {
                db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='0' WHERE group_id='$group_id'");
            }

            // get current information, force update on group and project objects
            $group = $pm->getProject($group_id, true);

            $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_index','changed_group_type'));
      }
      break;
    }
}

project_admin_header(array('title'=>$Language->getText('project_admin_index','p_admin',$group->getPublicName()),'group'=>$group_id,
			   'help' => 'project-admin.html'));

/*
	Show top box listing trove and other info
*/

echo '<TABLE width=100% border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top($Language->getText('project_admin_index','p_edit',$group->getPublicName()));

$hp =& Codendi_HTMLPurifier::instance();

print '&nbsp;
<BR>
'.$Language->getText('project_admin_index','short_desc',$hp->purify($group->getDescription(), CODENDI_PURIFIER_LIGHT));
if ($group->usesHomePage()) {
    print '<P>'.$Language->getText('project_admin_index','home_page_link',$group->getHomePage()).'</B>';
 }

if ($GLOBALS['sys_use_trove'] != 0) {
    print '
<P>
<B>'.$Language->getText('project_admin_index','trove_cat_info').'

<UL>';

    $trove_dao = new \Tuleap\TroveCat\TroveCatLinkDao();
    foreach ($trove_dao->searchTroveCatForProject($group_id) as $row_trovecat) {
        echo '<li>';
        echo $row_trovecat['fullpath'].' '.help_button('trove_cat',$row_trovecat['trove_cat_id']);
        echo '</li>';
    }

    print '
</UL>
<P align="center">
<A href="/project/admin/group_trove.php?group_id='.$group_id.'">'
.'<B>'.$Language->getText('project_admin_index','edit_trove_cat').'</B></A>
';
 }


// list all possible project types
// get current information
$template =& TemplateSingleton::instance();


print '
<HR NoShade SIZE="1">
<P>';
if (user_is_super_user()) {
    print '<TABLE WIDTH="100%" BORDER="0">
 <TR>
  <TD><B>'.$Language->getText('project_admin_index','group_type').' '.help_button('project-admin.html#project-type').' : </B>
      <FORM action="?" method="post">
      <INPUT TYPE="HIDDEN" NAME="func" VALUE="change_group_type">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'"></TD>
  <TD valign="top">'.$template->showTypeBox('form_project_type',$group->getType()).'
      <INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
      </FORM></TD>
 </TR>
</TABLE>
';
} else {
    print '<B>'.$Language->getText('project_admin_index','group_type').' '.help_button('project-admin.html#project-type').' : '.$template->getLabel($group->getType()).'</B>';
}

$template_group = $pm->getProject($group->getTemplate());
$template_name = $template_group->getPublicName();
print '
<HR NoShade SIZE="1">
<P>
'.$Language->getText('project_admin_index','built_from_template','<A href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_name.' </B></A>');

if ($group->isTemplate()) {
    echo '<hr NoShade SIZE="1" /><p><b>'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects') .':</b> <a id="show_projects_link" href="projects.php?group_id='. $group_id .'">'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects_show') .'</a></p><div id="show_projects_div"></div>';
    $show_projects_show = htmlentities($GLOBALS['Language']->getText('project_admin_index', 'show_projects_show'), ENT_QUOTES, 'UTF-8');
    $show_projects_hide = htmlentities($GLOBALS['Language']->getText('project_admin_index', 'show_projects_hide'), ENT_QUOTES, 'UTF-8');
    echo <<<EOS
    <script type="text/javascript">
    var show_projects_done = false;
    var show_projects_link_txt;
    Event.observe(window, 'load', function() {
        if ($('show_projects_link')) {
            Event.observe($('show_projects_link'), 'click', function (evt) {
                    if (!show_projects_done) {
                        new Ajax.Updater('show_projects_div', $('show_projects_link').href, {
                            onSuccess: function() {
                                show_projects_link_txt = '$show_projects_hide';
                                $('show_projects_link').update(show_projects_link_txt);
                                show_projects_done = true;
                            }
                        });
                    } else {
                        if (show_projects_link_txt == '$show_projects_hide') {
                            show_projects_link_txt = '$show_projects_show';
                        } else {
                            show_projects_link_txt = '$show_projects_hide';
                        }
                        $('show_projects_link').update(show_projects_link_txt);
                        $('show_projects_div').toggle();
                        Event.stop(evt);
                        return false;
                    }
                Event.stop(evt);
                return false;
            });
        }
    });
    </script>
EOS;
}

echo '<HR NoShade SIZE="1">';
$parent_project = $pm->getParentProject($group_id);
if ($parent_project) {
    echo $Language->getText('project_admin_editugroup', 'parent').' <a href="?group_id='.$parent_project->getID().'">'.$parent_project->getPublicName().'</a>';
} else {
    echo $Language->getText('project_admin_editugroup', 'no_parent');
}
echo ' &dash; <a href="editgroupinfo.php?group_id='.$group_id.'">'.$Language->getText('project_admin_editugroup', 'go_to_hierarchy_admin').'</a>';
$HTML->box1_bottom();

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


$HTML->box1_top($Language->getText('project_admin_editugroup','proj_members')."&nbsp;".help_button('project-admin.html#user-permissions'));

/*

	Show the members of this project

*/

$sql = "SELECT user.realname, user.user_id, user.user_name, user.status, IF(generic_user.group_id, 1, 0) AS is_generic
        FROM user_group
        INNER JOIN user ON (user.user_id = user_group.user_id)
        LEFT JOIN generic_user ON (
            generic_user.user_id = user.user_id AND
            generic_user.group_id = $group_id)
        WHERE user_group.group_id = $group_id
        ORDER BY user.realname";

$res_memb = db_query($sql);
print '<div  style="max-height:200px; overflow:auto;">';
print '<TABLE WIDTH="100%" BORDER="0">';
$user_helper = new UserHelper();

while ($row_memb=db_fetch_array($res_memb)) {
    $display_name = '';

    $em->processEvent('get_user_display_name', array(
        'user_id'           => $row_memb['user_id'],
        'user_name'         => $row_memb['user_name'],
        'realname'          => $row_memb['realname'],
        'user_display_name' => &$display_name
    ));
    if (!$display_name) {
        $display_name = $hp->purify($user_helper->getDisplayName($row_memb['user_name'], $row_memb['realname']));
    }

    $edit_settings = '';
    if ($row_memb['is_generic']) {
        $url   = '/project/admin/editgenericmember.php?group_id='. $group_id;
        $title = $GLOBALS['Language']->getText('project_admin', 'edit_generic_user_settings');

        $edit_settings  = '<a href="'. $url .'" title="'. $title .'">';
        $edit_settings .= $GLOBALS['HTML']->getImage('ic/edit.png');
        $edit_settings .= '</a>';
    }

    print '<FORM ACTION="?" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">'.
	'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'.$row_memb['user_id'].'">'.
	'<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">'.
	'<TR><TD class="delete-project-member"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
	'<TD><A href="/users/'.$row_memb['user_name'].'/">'. $display_name .' </A>'. $edit_settings .'</TD></TR>';
}

print '</TABLE></div> <HR NoShade SIZE="1">';

/*
	Add member form
*/

echo '
        <FORM ACTION="?" METHOD="POST" class="add-user">
        <INPUT TYPE="hidden" NAME="func" VALUE="adduser">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">

        <div class="control-group">
            <label class="control-label" for="add_user">'.$Language->getText('project_admin_index','login_name').'</label>
            <div class="input-append">
                <INPUT TYPE="TEXT" NAME="form_unix_name" VALUE="" id="add_user">
                <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('project_admin_index','add_user').'" class="btn">
            </div>
        </div>
';

// JS code for autocompletion on "add_user" field defined on top.
$js = "new UserAutoCompleter('add_user',
                          '".util_get_dir_image_theme()."',
                          false);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

echo '      </FORM>
';

$em->processEvent('project_admin_add_user_form', array('groupId' => $group_id));

echo '
         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userimport.php?group_id='. $group_id.'">'.$Language->getText('project_admin_index','import_user').'</A>       
         </div>
                
         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userperms.php?group_id='. $group_id.'">'.$Language->getText('project_admin_index','edit_member_perm').'</A>
         </div>
         </TD></TR>';

$HTML->box1_bottom();


echo '</TD></TR>

	<TR valign=top><TD width=50%>';

/*
	Links to Services administration pages
*/

$HTML->box1_top($Language->getText('project_admin_index','s_admin').'&nbsp;'.help_button('project-admin.html#services-administration'));


echo '
	<BR>';
if ($group->usesForum()) {
    echo '	<A HREF="/forum/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','forum_admin').'</A><BR>';
}
if ($group->usesMail()) {
    echo '	<A HREF="/mail/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','lists_admin').'</A><BR>';
}
if ($group->usesDocman()) {
    echo '	<A HREF="/docman/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','doc_admin').'</A><BR>';
}
if ($group->usesWiki()) {
    echo '	<A HREF="/wiki/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','wiki_admin').'</A><BR>';
}
if ($group->usesNews()) {
    echo '	<A HREF="/news/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','news_admin').'</A><BR>';
}
if ($group->usesCVS()) {
    echo '	<A HREF="/cvs/?func=admin&group_id='.$group_id.'">'.$Language->getText('project_admin_index','cvs_admin').'</A><BR>';
}
if ($group->usesSVN()) {
    echo '	<A HREF="/svn/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','svn_admin').'</A><BR>';
}
if ($group->usesFile()) {
    echo '	<A HREF="/file/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','file_admin').'</A><BR>';
}
if ( $group->usesTracker()) {
    echo '	<A HREF="/tracker/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','tracker_admin').'</A>';
    $atf = new ArtifactTypeFactory($group);
    if (!$group || !is_object($group) || $group->isError()) {
        exit_error($Language->getText('global','error'),'Could Not Get ArtifactTypeFactory');
    }

    // Get the artfact type list
    $at_arr = $atf->getArtifactTypes();

    if (!$at_arr || count($at_arr) < 1) {
        echo "<br><i>-&nbsp;".$Language->getText('project_admin_index','no_tracker_found').'</i>';
    } else {
        for ($j = 0; $j < count($at_arr); $j++) {
            echo '<br><i>-&nbsp;
			<a href="/tracker/admin/?atid='. $at_arr[$j]->getID() .
                '&group_id='.$group_id.'">' .
                $at_arr[$j]->getName() .' '.$Language->getText('project_admin_index','admin').'</a></i>';
        }
    }

}
// {{{ Plugins
$admin_pages = array();
$params = array('project' => &$group, 'admin_pages' => &$admin_pages);

$em->processEvent('service_admin_pages', $params);

foreach($admin_pages as $admin_page) {
    print '<br />';
    print $admin_page;
}

// }}}

$HTML->box1_bottom();




echo '</TD>

	<TD>&nbsp;</TD>

	<TD width=50%>';

/*
	Delegate notifications
*/
$HTML->box1_top($Language->getText('project_admin_index','member_request_delegation_title'));

//Retrieve the saved ugroups for notification from DB
$selectedUgroup = array();
$dar = $pm->getMembershipRequestNotificationUGroup($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
    foreach ($dar as $row) {
        if ($row['ugroup_id'] == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
            $selectedUgroup[] = util_translate_name_ugroup('project_admin');
        } else {
            $selectedUgroup[] = ugroup_get_name_from_id($row['ugroup_id']);
        }
    }
} else {
    $selectedUgroup = array(util_translate_name_ugroup('project_admin'));
}
echo '<b>'.$Language->getText('project_admin_utils','selected_ugroups_title').'</b>';
echo '<ul>';
foreach ($selectedUgroup as $ugroup) {
    echo '<li>'.$ugroup.'</li>';
}
echo '</ul>';
$message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
$pm = ProjectManager::instance();
$dar = $pm->getMessageToRequesterForAccessProject($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
    $row = $dar->current();
    if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
        $message = $row['msg_to_requester'];
    }
}
echo '<hr size="1" noshade="">';
echo '<b>'.$Language->getText('project_admin_utils','notif_message_title').'</b><br/>';
echo '<p><div class="admin_delegation">'.$message.'</div></p>';
echo '<hr size="1" noshade="">';
echo '<tr><td colspan="2">';
echo '<p align="center">';
echo '<A HREF="/project/admin/permission_request.php?group_id='.$group_id.'"><b>['.$Language->getText('project_admin_utils','permission_request').']</b></A>';
echo '</p></td></tr>';
echo $HTML->box1_bottom();
?>
</TD>
</TR>
</TABLE>

<?php
project_admin_footer(array());

?>
