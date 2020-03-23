<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\admin\ProjectEdit;

use Event;
use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use Rule_ProjectName;
use SystemEventManager;
use Tuleap\Project\Admin\ProjectDetailsPresenter;

class ProjectEditController
{
    public const TEMPLATE = 'project-info';
    /**
     * @var ProjectDetailsPresenter
     */
    private $details_presenter;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ProjectEditDao
     */
    private $dao;
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    public function __construct(
        ProjectDetailsPresenter $details_presenter,
        ProjectEditDao $dao,
        ProjectManager $project_manager,
        EventManager $event_manager,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $project_history_dao
    ) {
        $this->details_presenter    = $details_presenter;
        $this->dao                  = $dao;
        $this->project_manager      = $project_manager;
        $this->event_manager        = $event_manager;
        $this->system_event_manager = $system_event_manager;
        $this->project_history_dao  = $project_history_dao;
    }

    public function index()
    {
        $renderer      = new \Tuleap\Admin\AdminPageRenderer();
        $template_path = ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/';

        $renderer->renderANoFramedPresenter(
            $GLOBALS['Language']->getText('admin_groupedit', 'title'),
            $template_path,
            self::TEMPLATE,
            $this->details_presenter
        );
    }

    public function updateProject(HTTPRequest $request)
    {
        $new_name   = $request->get('new_name');
        $project_id = $request->get('group_id');
        $project    = $this->project_manager->getProject($project_id);

        if ($new_name && $new_name !== $project->getUnixNameMixedCase()) {
            $this->renameProject($project, $new_name);
        }

        $form_status  = $this->getProjectStatus($request, $project);
        $project_type = $request->getValidated('group_type', 'string', $project->getType());

        if ($this->hasStatusChanged($project, $form_status) || $this->hasTypeChanged($project, $project_type)) {
            if ($this->hasStatusChanged($project, $form_status) && $form_status === Project::STATUS_PENDING) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('Switching the project status back to "pending" is not possible.')
                );

                $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . urlencode($project_id));
            }

            $this->dao->updateProjectStatusAndType($form_status, $project_type, $project_id);

            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('admin_groupedit', 'feedback_info'));

            $this->propagateStatusChange($project, $form_status);

            if ($this->hasTypeChanged($project, $project_type)) {
                $this->project_history_dao->groupAddHistory('group_type', $project->getType(), $project_id);
            }

            $this->project_manager->removeProjectFromCache($project);
        }

        $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . urlencode($project_id));
    }

    private function propagateStatusChange(Project $project, $form_status)
    {
        if (! isset($form_status) && $form_status || ! $form_status) {
            return;
        }

        if ($this->hasStatusChanged($project, $form_status) && $project->getGroupId() !== Project::ADMIN_PROJECT_ID) {
            $old_status_label = $this->getStatusLabel($project->getStatus());
            $new_status_label = $this->getStatusLabel($form_status);
            $this->project_history_dao->groupAddHistory('status', $old_status_label . " :: " . $new_status_label, $project->group_id);

            $event_params = [
                'group_id' => $project->group_id
            ];

            if ($form_status === Project::STATUS_SUSPENDED) {
                $this->event_manager->processEvent(
                    'project_is_suspended',
                    $event_params
                );
            } elseif ($form_status === Project::STATUS_ACTIVE) {
                $this->event_manager->processEvent(
                    'project_is_active',
                    $event_params
                );
            } elseif ($form_status === Project::STATUS_DELETED) {
                $this->event_manager->processEvent(
                    'project_is_deleted',
                    $event_params
                );
            }
        }
    }

    private function getStatusLabel($status_code)
    {
        switch ($status_code) {
            case Project::STATUS_ACTIVE:
                return $GLOBALS['Language']->getText('admin_groupedit', 'status_A');
            case Project::STATUS_PENDING:
                return $GLOBALS['Language']->getText('admin_groupedit', 'status_P');
            case Project::STATUS_SUSPENDED:
                return $GLOBALS['Language']->getText('admin_groupedit', 'status_H');
            case Project::STATUS_DELETED:
                return $GLOBALS['Language']->getText('admin_groupedit', 'status_D');
            case Project::STATUS_SYSTEM:
                return $GLOBALS['Language']->getText('admin_groupedit', 'status_s');
        }

        throw new \RuntimeException("Unknown status $status_code");
    }

    private function renameProject(Project $project, $new_name)
    {
        if (! $this->system_event_manager->canRenameProject($project)) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('admin_groupedit', 'rename_project_already_queued'), CODENDI_PURIFIER_DISABLED);

            return;
        }

        $rule = new Rule_ProjectName();

        if (! $rule->isValid($new_name)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_groupedit', 'invalid_short_name'));
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $rule->getErrorMessage());

            return;
        }

        $this->event_manager->processEvent(
            Event::PROJECT_RENAME,
            [
                'group_id' => $project->getID(),
                'new_name' => $new_name
            ]
        );

        //update group history
        $this->project_history_dao->groupAddHistory('rename_request', $project->getUnixName(false) . ' :: ' . $new_name, $project->getID());

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'admin_groupedit',
                'rename_project_msg',
                [
                    $project->getUnixName(false),
                    $new_name
                ]
            )
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            $GLOBALS['Language']->getText('admin_groupedit', 'rename_project_warn')
        );
    }

    private function hasTypeChanged(Project $project, $project_type)
    {
        return $project->getType() !== $project_type;
    }

    private function hasStatusChanged(Project $project, $form_status)
    {
        return $project->getStatus() !== $form_status;
    }

    private function getProjectStatus(HTTPRequest $request, Project $project)
    {
        if ($project->getGroupId() !== Project::ADMIN_PROJECT_ID) {
            return $request->getValidated('form_status', 'string', $project->getStatus());
        }

        return $project->getStatus();
    }
}
