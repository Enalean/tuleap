<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

    public function missingKanbanTitle()
    {
        $this->notifyErrorAndRedirectToAdmin('kanban');
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
        $this->info($GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_activated'));
    }

    public function scrumActivated()
    {
        $this->info($GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_activated'));
    }

    public function emptyKanbanTitle()
    {
        $this->warn($GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_title_empty'));
    }

    public function emptyScrumTitle()
    {
        $this->warn($GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_title_empty'));
    }

    public function kanbanTitleChanged()
    {
        $this->info($GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_title_changed'));
    }

    public function scrumTitleChanged()
    {
        $this->info($GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_title_changed'));
    }

    private function notifyErrorAndRedirectToAdmin($pane)
    {
        $this->error($GLOBALS['Language']->getText('plugin_agiledashboard', 'invalid_request'));
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
        $query_parts = array(
            'group_id' => $this->project->getId(),
            'action'   => 'admin',
            'pane'     => $pane
        );
        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?' . http_build_query($query_parts));
    }

    private function redirectToHome()
    {
        $query_parts = array(
            'group_id' => $this->project->getId()
        );
        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?' . http_build_query($query_parts));
    }
}
