<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter;

use HTTPRequest;
use Project;
use Rule_Regexp;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Creation\TrackerCreationPermissionChecker;
use Valid_LocalURI;

class JiraTrackerListController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var TrackerCreationPermissionChecker
     */
    private $permission_checker;
    /**
     * @var JiraProjectBuilder
     */
    private $jira_project_builder;

    public function __construct(
        \ProjectManager $project_manager,
        TrackerCreationPermissionChecker $permission_checker,
        JiraProjectBuilder $jira_project_builder
    ) {
        $this->project_manager      = $project_manager;
        $this->permission_checker   = $permission_checker;
        $this->jira_project_builder = $jira_project_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $user    = $request->getCurrentUser();
        $this->permission_checker->checkANewTrackerCanBeCreated($project, $user);

        $body = $request->getJsonDecodedBody();

        try {
            if (! isset($body->credentials)) {
                throw JiraConnectionException::creadentialsKeyIsMissing();
            }

            if (
                ! isset($body->credentials->server_url)
                || ! isset($body->credentials->user_email)
                || ! isset($body->credentials->token)
            ) {
                throw JiraConnectionException::credentialsValuesAreMissing();
            }
            $jira_server = $body->credentials->server_url;
            $jira_user   = $body->credentials->user_email;
            $jira_token  = $body->credentials->token;

            $valid_http = new Rule_Regexp(Valid_LocalURI::URI_REGEXP);
            if (!$valid_http->isValid($jira_server)) {
                throw JiraConnectionException::urlIsInvalid();
            }

            $wrapper = $this->buildWrapper($jira_server, $jira_user, $jira_token);

            $projects = $this->jira_project_builder->build($wrapper);
            $layout->sendJSON($projects);
        } catch (JiraConnectionException $exception) {
            $layout->send400JSONErrors(['error' => $exception->getI18nMessage()]);
        } catch (\Exception $exception) {
            $layout->send400JSONErrors(['error' => $exception->getMessage()]);
        }
    }

    /**
     * @throws \Project_NotFoundException
     */
    public function getProject(array $variables): Project
    {
        return $this->project_manager->getValidProjectByShortNameOrId($variables['project_name']);
    }

    /**
     * for testing purpose
     */
    protected function buildWrapper(string $jira_server, string $jira_user, string $jira_token): ClientWrapper
    {
        return ClientWrapper::build($jira_server, $jira_user, $jira_token);
    }
}
