<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class AgileDashboardConfigurationResponse
{
    /** @var Project */
    private $project;

    /** @var bool */
    private $redirect_to_home_on_success;

    public function __construct(Project $project, $redirect_to_home_on_success)
    {
        $this->project                     = $project;
        $this->redirect_to_home_on_success = $redirect_to_home_on_success;
    }

    public function missingScrumTitle()
    {
        $this->notifyErrorAndRedirectToAdmin('scrum');
    }

    public function deactivateExplicitTopBacklogNotAllowed()
    {
        $this->notifyErrorAndRedirectToAdmin('scrum');
    }

    public function kanbanConfigurationUpdated()
    {
        if ($this->redirect_to_home_on_success) {
            $this->redirectToHome();
            return;
        }

        $this->redirectToAdmin('kanban');
    }

    public function scrumConfigurationUpdated()
    {
        if ($this->redirect_to_home_on_success) {
            $this->redirectToHome();
            return;
        }

        $this->redirectToAdmin('scrum');
    }

    public function kanbanActivated()
    {
        $this->info(dgettext('tuleap-agiledashboard', 'Kanban successfully activated.'));
    }

    public function scrumActivated()
    {
        $this->info(dgettext('tuleap-agiledashboard', 'Scrum successfully activated.'));
    }

    public function emptyScrumTitle()
    {
        $this->warn(dgettext('tuleap-agiledashboard', 'Scrum title is empty.'));
    }

    public function scrumTitleChanged()
    {
        $this->info(dgettext('tuleap-agiledashboard', 'Scrum title successfully modified.'));
    }

    private function notifyErrorAndRedirectToAdmin($pane)
    {
        $this->error(dgettext('tuleap-agiledashboard', 'The request is invalid.'));
        $this->redirectToAdmin($pane);
    }

    private function info($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $message);
    }

    private function warn($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $message);
    }

    private function error($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $message);
    }

    private function redirectToAdmin($pane)
    {
        $query_parts = [
            'group_id' => $this->project->getId(),
            'action'   => 'admin',
            'pane'     => $pane,
        ];
        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?' . http_build_query($query_parts));
    }

    private function redirectToHome()
    {
        $query_parts = [
            'group_id' => $this->project->getId(),
        ];
        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?' . http_build_query($query_parts));
    }
}
