<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuealp\project\Admin;

use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use Event;
use EventManager;
use ProjectManager;
use ProjectTruncatedEmailsPresenter;
use ProjectVisibilityPresenter;
use Service;
use TemplateRendererFactory;

class ProjectVisibilityController
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var ProjectVisibilityUserConfigurationPermissions
     */
    private $project_visibility_configuration;

    /**
     * @var ServicesUsingTruncatedMailRetriever
     */
    private $service_truncated_mails_retriever;

    public function __construct(
        ProjectManager $project_manager,
        ProjectVisibilityUserConfigurationPermissions $project_visibility_configuration,
        ServicesUsingTruncatedMailRetriever $service_truncated_mails_retriever
    ) {
        $this->project_manager                   = $project_manager;
        $this->project_visibility_configuration  = $project_visibility_configuration;
        $this->service_truncated_mails_retriever = $service_truncated_mails_retriever;
    }

    public function display(HTTPRequest $request)
    {
        $project      = $request->getProject();
        $current_user = $request->getCurrentUser();

        $visibility_presenter = new ProjectVisibilityPresenter(
            $GLOBALS['Language'],
            ForgeConfig::areRestrictedUsersAllowed(),
            $project->getAccess(),
            $this->project_visibility_configuration->canUserConfigureProjectVisibility($current_user, $project)
        );


        $truncated_mails_impacted_services = $this->service_truncated_mails_retriever->getServicesImpactedByTruncatedEmails($project);
        $truncated_presenter               = new ProjectTruncatedEmailsPresenter(
            $project,
            $truncated_mails_impacted_services,
            $this->project_visibility_configuration->canUserConfigureTruncatedMail(
                $current_user
            )
        );

        $presenter = new ProjectGlobalVisibilityPresenter(
            $project,
            $visibility_presenter,
            $truncated_presenter,
            $this->project_visibility_configuration->canUserConfigureSomething(
                $current_user,
                $project
            )
        );

        $this->displayHeader($project);
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/project/'
        );

        echo $renderer->renderToString('project-visibility-form', $presenter);
    }

    public function update(HTTPRequest $request)
    {
        $project      = $request->getProject();
        $current_user = $request->getCurrentUser();

        $this->updateProjectVisibility($current_user, $project, $request);
        $this->updateTruncatedMails($current_user, $project, $request);

        $GLOBALS['Response']->addFeedback('info', _("Update successful"));
        $GLOBALS['Response']->redirect(
            '/project/admin/project_visibility.php?' .
            http_build_query(array('group_id' => $request->getProject()->getid()))
        );
    }

    private function updateTruncatedMails(PFUser $user, Project $project, HTTPRequest $request)
    {
        if ($this->project_visibility_configuration->canUserConfigureTruncatedMail($user)) {
            $usage = (int) $request->exist('truncated_emails');
            if ($project->getTruncatedEmailsUsage() != $usage) {
                $this->project_manager->setTruncatedEmailsUsage($project, $usage);
            }
        }
    }

    private function updateProjectVisibility(PFUser $user, Project $project, HTTPRequest $request)
    {
        if ($this->project_visibility_configuration->canUserConfigureProjectVisibility($user, $project)) {
            if ($project->getAccess() != $request->get('project_visibility')) {
                $this->project_manager->setAccess($project, $request->get('project_visibility'));
            }
        }
    }

    private function displayHeader(Project $project)
    {
        project_admin_header(
            array(
                'title' => $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'editing_g_info'),
                'group' => $project->getGroupId(),
                'help'  => 'project-admin.html#project-public-information'
            )
        );
    }
}
