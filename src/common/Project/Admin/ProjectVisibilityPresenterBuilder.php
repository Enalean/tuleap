<?php
/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\Project\Admin;

use ForgeConfig;
use HTTPRequest;
use Project;
use ProjectTruncatedEmailsPresenter;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityChecker;
use Tuleap\Project\ProjectAccessPresenter;

class ProjectVisibilityPresenterBuilder
{
    /**
     * @var ProjectVisibilityUserConfigurationPermissions
     */
    private $project_visibility_configuration;

    /**
     * @var ServicesUsingTruncatedMailRetriever
     */
    private $service_truncated_mails_retriever;
    /**
     * @var RestrictedUsersProjectCounter
     */
    private $restricted_users_project_counter;
    /**
     * @var ProjectVisibilityOptionsForPresenterGenerator
     */
    private $project_visibility_options_generator;

    public function __construct(
        ProjectVisibilityUserConfigurationPermissions $project_visibility_configuration,
        ServicesUsingTruncatedMailRetriever $service_truncated_mails_retriever,
        RestrictedUsersProjectCounter $restricted_users_project_counter,
        ProjectVisibilityOptionsForPresenterGenerator $project_visibility_options_generator,
        private readonly UpdateVisibilityChecker $update_visibility_checker,
    ) {
        $this->project_visibility_configuration     = $project_visibility_configuration;
        $this->service_truncated_mails_retriever    = $service_truncated_mails_retriever;
        $this->restricted_users_project_counter     = $restricted_users_project_counter;
        $this->project_visibility_options_generator = $project_visibility_options_generator;
    }

    public function build(HTTPRequest $request)
    {
        $project              = $request->getProject();
        $current_user         = $request->getCurrentUser();
        $visibility_presenter = new ProjectVisibilityPresenter(
            $GLOBALS['Language'],
            ForgeConfig::areRestrictedUsersAllowed(),
            $this->update_visibility_checker->canUpdateVisibilityRegardingRestrictedUsers(
                $project,
                Project::ACCESS_PRIVATE_WO_RESTRICTED,
            ),
            $project->getAccess(),
            $this->restricted_users_project_counter->getNumberOfRestrictedUsersInProject($project),
            $this->project_visibility_options_generator
        );

        $truncated_mails_impacted_services = $this->service_truncated_mails_retriever->getServicesImpactedByTruncatedEmails($project);
        $truncated_presenter               = new ProjectTruncatedEmailsPresenter(
            $project,
            $truncated_mails_impacted_services,
            $this->project_visibility_configuration->canUserConfigureTruncatedMail(
                $current_user
            )
        );

        $can_user_configure_something = $this->project_visibility_configuration->canUserConfigureSomething($current_user, $project);
        $project_access_presenter     = new ProjectAccessPresenter($request->getProject()->getAccess());

        return new ProjectGlobalVisibilityPresenter(
            $project,
            $visibility_presenter,
            $truncated_presenter,
            $project_access_presenter,
            $can_user_configure_something
        );
    }
}
