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
if (! $request->valid($vGroupId)) {
    exit_error(
        $Language->getText('project_admin_index', 'invalid_p'),
        $Language->getText('project_admin_index', 'p_not_found')
    );
}
$group_id = $request->get('group_id');

//must be a project admin
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

//
//  get the Group object
//
$pm    = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (! $group || ! is_object($group) || $group->isError()) {
    exit_no_group();
}

//if the project isn't active, require you to be a member of the super-admin group
if ($group->getStatus() != 'A') {
    $request->checkUserIsSuperUser();
}

$em = EventManager::instance();

$vFunc = new Valid_WhiteList(
    'func',
    array(
        'adduser',
        'rmuser'
    )
);
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    /*
    updating the database
    */

    $ugroup_user_dao = new UGroupUserDao();
    $ugroup_manager  = new UGroupManager();

    $ugroup_binding = new UGroupBinding(
        $ugroup_user_dao,
        $ugroup_manager
    );

    switch ($request->get('func')) {
        case 'adduser':
            // add user to this project
            $form_unix_name = $request->get('form_unix_name');
            $res            = account_add_user_to_group($group_id, $form_unix_name);
            $ugroup_binding->reloadUgroupBindingInProject($group);
            break;

        case 'rmuser':
            // remove a user from this portal
            $rm_id        = $request->getValidated('rm_id', 'uint', 0);
            $user_remover = new \Tuleap\Project\UserRemover(
                ProjectManager::instance(),
                EventManager::instance(),
                new ArtifactTypeFactory(false),
                new \Tuleap\Project\UserRemoverDao(),
                UserManager::instance(),
                new ProjectHistoryDao(),
                $ugroup_manager
            );
            $user_remover->removeUserFromProject($group_id, $rm_id);
            $ugroup_binding->reloadUgroupBindingInProject($group);
            break;
    }
}

project_admin_header(
    array(
        'title' => $Language->getText('project_admin_index', 'p_admin', $group->getPublicName()),
        'group' => $group_id,
        'help'  => 'project-admin.html'
    )
);

/*
Show top box listing trove and other info
*/

echo '<TABLE width=100% border=0>
<TR valign=top>';

$hp = Codendi_HTMLPurifier::instance();

echo '<TD width=50%>';


$HTML->box1_top(
    $Language->getText('project_admin_editugroup', 'proj_members') . "&nbsp;" .
    help_button('project-admin.html#user-permissions')
);

/*

Show the members of this project

*/
$sql = "SELECT user.realname, user.user_id, user.user_name, user.status, IF(generic_user.group_id, 1, 0) AS is_generic
FROM user_group
INNER JOIN user ON (user.user_id = user_group.user_id)
LEFT JOIN generic_user ON (
generic_user.user_id = user.user_id AND
generic_user.group_id = " . db_ei($group_id) . ")
WHERE user_group.group_id = " . db_ei($group_id) . "
ORDER BY user.realname";

$res_memb = db_query($sql);
print '<div  style="max-height:200px; overflow:auto;">';
print '<TABLE WIDTH="100%" BORDER="0">';
$user_helper = new UserHelper();

while ($row_memb = db_fetch_array($res_memb)) {
    $display_name = $hp->purify($user_helper->getDisplayName($row_memb['user_name'], $row_memb['realname']));

    $edit_settings = '';
    if ($row_memb['is_generic']) {
        $url   = '/project/admin/editgenericmember.php?group_id=' . urlencode($group_id);
        $title = $GLOBALS['Language']->getText('project_admin', 'edit_generic_user_settings');

        $edit_settings = '<a href="' . $url . '" title="' . $title . '">';
        $edit_settings .= $GLOBALS['HTML']->getImage('ic/edit.png');
        $edit_settings .= '</a>';
    }

    print '<FORM ACTION="?" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">' .
        '<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="' . $row_memb['user_id'] . '">' .
        '<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">' .
        '<TR><TD class="delete-project-member"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="' . util_get_image_theme(
            "ic/trash.png"
        ) . '" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>' .
        '<TD><A href="/users/' . $row_memb['user_name'] . '/">' . $display_name . ' </A>' . $edit_settings . '</TD></TR>';
}

print '</TABLE></div> <HR NoShade SIZE="1">';

/*
Add member form
*/

echo '
<FORM ACTION="?" METHOD="POST" class="add-user">
<INPUT TYPE="hidden" NAME="func" VALUE="adduser">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">

<div class="control-group">
<label class="control-label" for="add_user">' . $Language->getText('project_admin_index', 'login_name') . '</label>
<div class="input-append">
<INPUT TYPE="TEXT" NAME="form_unix_name" VALUE="" id="add_user">
<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $Language->getText('project_admin_index', 'add_user') . '" class="btn">
</div>
</div>
';

// JS code for autocompletion on "add_user" field defined on top.
$js = "new UserAutoCompleter('add_user',
'" . util_get_dir_image_theme() . "',
false);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

echo '      </FORM>
';

$em->processEvent('project_admin_add_user_form', array('groupId' => $group_id));

echo '
<HR NoShade SIZE="1">
<div align="center">
<A href="/project/admin/userimport.php?group_id='. urlencode($group_id) . '">' . $Language->getText(
    'project_admin_index',
    'import_user'
) . '</A>
</div>

</TD></TR>';

echo $HTML->box1_bottom();

echo '<TD width=50%>';

/*
Links to Services administration pages
*/
$HTML->box1_top(
    $Language->getText('project_admin_index', 's_admin') . '&nbsp;' . help_button(
        'project-admin.html#services-administration'
    )
);

// {{{ Plugins
$admin_pages = array();
$params      = array('project' => &$group, 'admin_pages' => &$admin_pages);

$em->processEvent('service_admin_pages', $params);

foreach ($admin_pages as $admin_page) {
    print '<br />';
    print $admin_page;
}

// }}}

$HTML->box1_bottom();

echo'
    </TD>
    </TR>
    </TABLE>';

project_admin_footer(array());
