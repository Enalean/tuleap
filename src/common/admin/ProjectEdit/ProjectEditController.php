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
use Tuleap\Project\Admin\ProjectRenameChecker;

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
    /**
     * @var ProjectRenameChecker
     */
    private $project_rename_checker;

    public function __construct(
        ProjectDetailsPresenter $details_presenter,
        ProjectEditDao $dao,
        ProjectManager $project_manager,
        EventManager $event_manager,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $project_history_dao,
        ProjectRenameChecker $project_rename_checker,
    ) {
        $this->details_presenter      = $details_presenter;
        $this->dao                    = $dao;
        $this->project_manager        = $project_manager;
        $this->event_manager          = $event_manager;
        $this->system_event_manager   = $system_event_manager;
        $this->project_history_dao    = $project_history_dao;
        $this->project_rename_checker = $project_rename_checker;
    }

    public function index()
    {
        $renderer      = new \Tuleap\Admin\AdminPageRenderer();
        $template_path = ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/';

        $renderer->renderANoFramedPresenter(
            _('Editing Project'),
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
            if (! $this->checkIfStatusCanBeChanged($project, $form_status)) {
                return;
            }

            $this->dao->updateProjectStatusAndType($form_status, $project_type, $project_id);

            $GLOBALS['Response']->addFeedback(Feedback::INFO, _('Updating Project Info'));

            $this->propagateStatusChange($project, $form_status);

            if ($this->hasTypeChanged($project, $project_type)) {
                $this->project_history_dao->groupAddHistory('group_type', $project->getType(), $project_id);
            }

            $this->project_manager->removeProjectFromCache($project);
        }

        $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . urlencode($project_id));
    }

    private function propagateStatusChange(Project $project, string $form_status): void
    {
        if (! in_array($form_status, [Project::STATUS_SUSPENDED, Project::STATUS_ACTIVE, Project::STATUS_DELETED], true)) {
            return;
        }

        if ($this->hasStatusChanged($project, $form_status) && $project->getGroupId() !== Project::DEFAULT_TEMPLATE_PROJECT_ID) {
            $old_status_label = $this->getStatusLabel($project->getStatus());
            $new_status_label = $this->getStatusLabel($form_status);
            $this->project_history_dao->groupAddHistory('status', $old_status_label . " :: " . $new_status_label, $project->group_id);

            $this->event_manager->dispatch(new ProjectStatusUpdate($project, $form_status));
        }
    }

    private function getStatusLabel($status_code)
    {
        switch ($status_code) {
            case Project::STATUS_ACTIVE:
                return _('Active');
            case Project::STATUS_PENDING:
                return _('Pending');
            case Project::STATUS_SUSPENDED:
                return _('Suspended');
            case Project::STATUS_DELETED:
                return _('Deleted');
            case Project::STATUS_SYSTEM:
                return _('System');
        }

        throw new \RuntimeException("Unknown status $status_code");
    }

    private function renameProject(Project $project, $new_name)
    {
        if (! $this->system_event_manager->canRenameProject($project)) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, _('There is already an event scheduled to rename this project. Please check <a href="/admin/system_events/">System Event Monitor</a>.'), CODENDI_PURIFIER_DISABLED);

            return;
        }

        if (! $this->project_rename_checker->isProjectUnixNameEditable($project)) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, dgettext('tuleap-core', "This project doesn't allow short name edition."));
            return;
        }

        $rule = new Rule_ProjectName();

        if (! $rule->isValid($new_name)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Invalid Short Name'));
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $rule->getErrorMessage());

            return;
        }

        $this->event_manager->processEvent(
            Event::PROJECT_RENAME,
            [
                'group_id' => $project->getID(),
                'new_name' => $new_name,
            ]
        );

        //update group history
        $this->project_history_dao->groupAddHistory('rename_request', $project->getUnixName(false) . ' :: ' . $new_name, $project->getID());

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(_('Propagation of project\'s name update on system queued (%1$s -> %2$s). It will be processed in a little while.'), $project->getUnixName(false), $new_name)
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            _('Project name update will be effective only after system event processing.')
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
        if ($project->getGroupId() !== Project::DEFAULT_TEMPLATE_PROJECT_ID) {
            return $request->getValidated('form_status', 'string', $project->getStatus());
        }

        return $project->getStatus();
    }

    private function checkIfStatusCanBeChanged(Project $project, string $form_status): bool
    {
        if ($this->hasStatusChanged($project, $form_status)) {
            if ($form_status === Project::STATUS_PENDING) {
                $feedback_message = _('Switching the project status back to "pending" is not possible.');
                $this->sendErrorFeedbackAndRedirect($project->getID(), $feedback_message);
                return false;
            }
            if ($project->getStatus() === Project::STATUS_DELETED) {
                $feedback_message = _('A deleted project can not be restored.');
                $this->sendErrorFeedbackAndRedirect($project->getID(), $feedback_message);
                return false;
            }
        }
        return true;
    }

    private function sendErrorFeedbackAndRedirect(int $project_id, string $message): void
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );

        $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . urlencode((string) $project_id));
    }
}
