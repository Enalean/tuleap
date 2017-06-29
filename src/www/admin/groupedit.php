<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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

use Tuleap\Project\Admin\DescriptionFields\ProjectDescriptionFieldBuilder;
use Tuleap\Project\ProjectAccessPresenter;
use Tuleap\Project\Admin\ProjectDetailsPresenter;

require_once('pre.php');
require_once('vars.php');
require_once('www/admin/admin_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('www/project/export/project_export_utils.php');
require_once('www/project/admin/project_history.php');

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$pm            = ProjectManager::instance();
$event_manager = EventManager::instance();
$group = $pm->getProject($group_id);
if (!$group || $group->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_groupedit','error_group'));
    $GLOBALS['Response']->redirect('/admin');
}

if ($request->exist('update')) {
    $new_name = $request->get('new_name');
    if ($new_name && $new_name !== $group->getUnixNameMixedCase()) {
        if (SystemEventManager::instance()->canRenameProject($group)) {
            $rule = new Rule_ProjectName();
            if (!$rule->isValid($new_name)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_groupedit','invalid_short_name'));
                $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            } else {
                $event_manager->processEvent(Event::PROJECT_RENAME, array('group_id' => $group_id,
                                                           'new_name' => $new_name));
                //update group history
                group_add_history('rename_request', $group->getUnixName(false).' :: '.$new_name, $group_id);
                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_groupedit','rename_project_msg', array($group->getUnixName(false), $new_name )));
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit','rename_project_warn'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit', 'rename_project_already_queued'), CODENDI_PURIFIER_DISABLED);
        }
    }

    $form_status = $group->getStatus();
    if ($group->getGroupId() != Project::ADMIN_PROJECT_ID) {
        $form_status  = $request->getValidated('form_status', 'string', $group->getStatus());
    }
    if (! $form_status) {
        $form_status = $group->getStatus();
    }
    $group_type = $request->getValidated('group_type', 'string', $group->getType());

    if ($group->getStatus() != $form_status || $group->getType() != $group_type) {
        $sql = "UPDATE groups
            SET status = '" . db_es($form_status) . "', type = '" . db_es($group_type)."'
            WHERE group_id = "  .db_ei($group_id);

        db_query($sql);

        $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_groupedit', 'feedback_info'));

        if ($group->getStatus() != $form_status && $group->getGroupId() != Project::ADMIN_PROJECT_ID) {
            group_add_history('status', $Language->getText('admin_groupedit', 'status_' . $group->getStatus()) . " :: " . $Language->getText('admin_groupedit', 'status_' . $form_status), $group_id);
            if (isset($form_status) && $form_status && ($form_status == "H" || $form_status == "P")) {
                $event_manager->processEvent('project_is_suspended_or_pending', array(
                    'group_id' => $group_id
                ));
            } else if (isset($form_status) && $form_status && $form_status == "A") {
                $event_manager->processEvent('project_is_active', array(
                    'group_id' => $group_id
                ));
            } else if (isset($form_status) && $form_status && $form_status == "D") {
                $event_manager->processEvent('project_is_deleted', array('group_id' => $group_id));
            }
        }

        if ($group->getType() != $group_type) {
            group_add_history('group_type', $group->getType(), $group_id);
        }
    }

    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id='.$group_id);
}

$fields_factory            = new Tuleap\Project\DescriptionFieldsFactory(new Tuleap\Project\DescriptionFieldsDao());
$description_field_builder = new ProjectDescriptionFieldBuilder($fields_factory);
$all_custom_fields         = $description_field_builder->build($group);

$renderer = new \Tuleap\Admin\AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    $Language->getText('admin_groupedit', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'project-info',
    new ProjectDetailsPresenter($group, $all_custom_fields, new ProjectAccessPresenter($group->getAccess()))
);
