<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectPendingPresenter;


require_once('pre.php');
require_once('vars.php');
require_once('account.php');
require_once('proj_email.php');
require_once('www/admin/admin_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/event/EventManager.class.php');



$user                             = UserManager::instance()->getCurrentUser();
$forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
    new User_ForgeUserGroupPermissionsDao()
);
$special_access                   = $forge_ugroup_permissions_manager->doesUserHavePermission(
    $user, new User_ForgeUserGroupPermission_ProjectApproval()
);

if (! $special_access) {
    session_require(array('group' => '1', 'admin_flags' => 'A'));
}

$action = $request->getValidated('action', 'string', '');

$event_manager   = EventManager::instance();
$project_manager = ProjectManager::instance();
$csrf_token      = new CSRFSynchronizerToken('/admin/approve-pending.php');

// group public choice
if ($action === 'activate') {
    $csrf_token->check();
    $groups = array();
    if ($request->exist('list_of_groups')) {
        $groups = array_filter(array_map('intval', explode(",", $request->get('list_of_groups'))));
    }
    foreach ($groups as $group_id) {
        $project = $project_manager->getProject($group_id);
        $project_manager->activate($project);
    }
    if ($special_access) {
        $GLOBALS['Response']->redirect('/my/');
    } else {
        $GLOBALS['Response']->redirect('/admin/');
    }

} else if ($action === 'delete') {
    $csrf_token->check();
    $project = $project_manager->getProject($group_id);
    group_add_history('deleted', 'x', $project->getID());
    $project_manager->updateStatus($project, 'D');

    $event_manager->processEvent('project_is_deleted', array('group_id' => $group_id));
    if ($special_access) {
        $GLOBALS['Response']->redirect('/my/');
    } else {
        $GLOBALS['Response']->redirect('/admin/');
    }
}

// get current information
$res_grp = $project_manager->getAllPendingProjects();
if (count($res_grp) === 0) {
    $siteadmin = new AdminPageRenderer();
    $presenter = new ProjectPendingPresenter();

    $siteadmin->renderAPresenter(
        $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending'),
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
        'project-pending',
        $presenter
    );
} else {
    site_admin_header(array('title'=>$GLOBALS['Language']->getText('admin_approve_pending','title'), 'main_classes' => array('tlp-framed')));

    $arr = array();
    foreach ($res_grp as $row_grp) {

        ?>
        <fieldset>
            <legend style="font-size:1.3em; font-weight: bold;"><?php echo $row_grp->getPublicName() ?></legend>

<?php
        $group = $project_manager->getProject($row_grp->getID());

        $currentproject= $project_manager->getProject($row_grp->getID());


        $members_id = $group->getMembersId();
        if (count($members_id) > 0) {
            $admin_id = $members_id[0]; // the first (and normally the only one) is the project creator)
            $admin = UserManager::instance()->getUserById($admin_id);
            if ($admin->getID() != 0) {
                $project_date_creation = util_timestamp_to_userdateformat($group->getStartDate());
                // Display the project admin (the project creator) and the creation date
                echo $GLOBALS['Language']->getText('admin_approve_pending', 'creator_and_creation_date', array($admin->getID(), $admin->getName(), $project_date_creation));
            }
        }
?>

        <p>
        <A href="/admin/groupedit.php?group_id=<?php echo $row_grp->getID(); ?>"><b><?php echo $GLOBALS['Language']->getText('admin_groupedit','proj_edit'); ?></b></A> |
        <A href="/project/admin/?group_id=<?php echo $row_grp->getID(); ?>"><b><?php echo $GLOBALS['Language']->getText('admin_groupedit','proj_admin'); ?></b></A> |
        <A href="userlist.php?group_id=<?php print $row_grp->getID(); ?>"><b><?php echo $GLOBALS['Language']->getText('admin_groupedit','proj_member'); ?></b></A>

        <p>

        <B><?php
        // Get project type label
        $template = TemplateSingleton::instance();
        echo $GLOBALS['Language']->getText('admin_groupedit','group_type'); ?>: <?php echo $template->getLabel($row_grp->getType()); ?></B>
        <BR><B><?php echo $GLOBALS['Language']->getText('admin_groupedit','home_box'); ?>: <?php print $row_grp->getUnixBox(); ?></B>
        <BR><B><?php echo $GLOBALS['Language']->getText('admin_groupedit','http_domain'); ?>: <?php print $row_grp->getHTTPDomain(); ?></B>

        <br>
        &nbsp;
        <?php
        $res_cat = db_query("SELECT category.category_id AS category_id,"
            . "category.category_name AS category_name FROM category,group_category "
            . "WHERE category.category_id=group_category.category_id AND "
            . "group_category.group_id=".$row_grp->getID());
        while ($row_cat = db_fetch_array($res_cat)) {
            print "<br>$row_cat[category_name] "
            . '<A href="groupedit.php?group_id='. $row_grp->getID()
            .'&amp;group_idrm='. $row_grp->getID()
            .'&amp;form_catrm='. $row_cat['category_id'] .'">'
            . "[".$GLOBALS['Language']->getText('admin_approve_pending','remove_category')."]</A>";
        }

        // ########################## OTHER INFO

        print "<P><B>".$GLOBALS['Language']->getText('admin_groupedit','other_info')."</B>";
        print "<br><u>".$GLOBALS['Language']->getText('admin_groupedit','public')."</u>: ". ($row_grp->getAccess() !== Project::ACCESS_PRIVATE ? $GLOBALS['Language']->getText('global', 'yes') :  $GLOBALS['Language']->getText('global', 'no'));

        print "<br><u>".$GLOBALS['Language']->getText('admin_groupedit','unix_grp')."</u>: ".$row_grp->getUnixNameMixedCase();


    	$currentproject->displayProjectsDescFieldsValue();

        $template_group = ProjectManager::instance()->getProject($row_grp->getTemplate());
        print "<br><u>".$GLOBALS['Language']->getText('admin_groupedit','built_from_template').'</u>: <br> <A href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_group->getPublicname().' </B></A>';

        ?>
        <TABLE>
            <TR>
            <TD>
        <FORM action="#" method="POST">
        <?php echo $csrf_token->fetchHTMLInput() ?>
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
        <INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="<?php print $row_grp->getID(); ?>">
        <INPUT type="submit" name="submit" class="tlp-button-secondary" value="<?php echo $GLOBALS['Language']->getText('admin_approve_pending','approve'); ?>">
        </FORM>
        </TD>

            <TD>&nbsp;</TD>

            <TD>
        <FORM action="?" method="POST">
        <?php echo $csrf_token->fetchHTMLInput() ?>
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="delete">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php print $row_grp->getID(); ?>">
        <INPUT type="submit" name="submit" class="tlp-button-secondary" value="<?php echo $GLOBALS['Language']->getText('admin_approve_pending','delete'); ?>">
        </FORM>
            </TD>
            </TR>
            </TABLE>
        </fieldset><br />
        <?php

        $arr[] = $row_grp->getID();

    }

    //list of group_id's of pending projects
    $group_list=implode($arr,',');

    echo '
        <CENTER>
        <FORM action="?" method="POST">
        ' . $csrf_token->fetchHTMLInput() . '
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
        <INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="'.$group_list.'">
        <INPUT type="submit" name="submit" class="tlp-button-primary" value="'.$GLOBALS['Language']->getText('admin_approve_pending','approve_all').'">
        </FORM>
        </center>
        ';

    site_admin_footer(array());

}
