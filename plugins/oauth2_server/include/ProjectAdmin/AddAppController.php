<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\ProjectAdmin;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\InvalidAppDataException;
use Tuleap\OAuth2Server\App\NewOAuth2App;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;

final class AddAppController implements DispatchableWithRequest
{
    /** @var ProjectRetriever */
    private $project_retriever;
    /** @var ProjectAdministratorChecker */
    private $administrator_checker;
    /** @var AppDao */
    private $app_dao;
    /** @var \CSRFSynchronizerToken */
    private $csrf_token;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        AppDao $app_dao,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->app_dao               = $app_dao;
        $this->csrf_token            = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new AppDao(),
            new \CSRFSynchronizerToken(ListAppsController::CSRF_TOKEN)
        );
    }

    public static function getUrl(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin/add-app', $project->getID());
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

        $list_clients_url = ListAppsController::getUrl($project);
        $this->csrf_token->check($list_clients_url);

        $raw_app_name          = (string) $request->get('name');
        $raw_redirect_endpoint = (string) $request->get('redirect_uri');
        try {
            $app_to_be_saved = NewOAuth2App::fromAppData($raw_app_name, $raw_redirect_endpoint, $project);
        } catch (InvalidAppDataException $e) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-oauth2_server', 'The provided app data is not valid.')
            );
            $layout->redirect($list_clients_url);
            return;
        }
        $this->app_dao->create($app_to_be_saved);

        $layout->redirect($list_clients_url);
    }
}
