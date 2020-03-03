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
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\InvalidAppDataException;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\NewOAuth2App;
use Tuleap\OAuth2Server\App\PrefixOAuth2ClientSecret;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\ServiceInstrumentation;
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
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var LastCreatedOAuth2AppStore
     */
    private $last_created_app_store;
    /** @var \CSRFSynchronizerToken */
    private $csrf_token;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        AppDao $app_dao,
        SplitTokenVerificationStringHasher $hasher,
        LastCreatedOAuth2AppStore $last_created_app_store,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_retriever      = $project_retriever;
        $this->administrator_checker  = $administrator_checker;
        $this->app_dao                = $app_dao;
        $this->hasher                 = $hasher;
        $this->last_created_app_store = $last_created_app_store;
        $this->csrf_token             = $csrf_token;
    }

    public static function buildSelf(): self
    {
        $storage =& $_SESSION ?? [];
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastCreatedOAuth2AppStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \CSRFSynchronizerToken(ListAppsController::CSRF_TOKEN)
        );
    }

    public static function getUrl(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin/add-app', $project->getID());
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment(\oauth2_serverPlugin::SERVICE_NAME_INSTRUMENTATION);
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

        $list_clients_url = ListAppsController::getUrl($project);
        $this->csrf_token->check($list_clients_url);

        $raw_app_name          = (string) $request->get('name');
        $raw_redirect_endpoint = (string) $request->get('redirect_uri');
        try {
            $app_to_be_saved = NewOAuth2App::fromAppData($raw_app_name, $raw_redirect_endpoint, $project, $this->hasher);
        } catch (InvalidAppDataException $e) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-oauth2_server', 'The provided app data is not valid.')
            );
            $layout->redirect($list_clients_url);
            return;
        }
        $app_id = $this->app_dao->create($app_to_be_saved);

        $this->last_created_app_store->storeLastCreatedApp($app_id, $app_to_be_saved);

        $layout->redirect($list_clients_url);
    }
}
