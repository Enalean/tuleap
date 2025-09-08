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

namespace Tuleap\OAuth2Server\Administration;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\Administration\ProjectAdmin\ListAppsController;
use Tuleap\OAuth2Server\Administration\SiteAdmin\SiteAdminListAppsController;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\InvalidAppDataException;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecretStore;
use Tuleap\OAuth2ServerCore\App\NewOAuth2App;
use Tuleap\Request\DispatchablePSR15Compatible;

final class AddAppController extends DispatchablePSR15Compatible
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var AppDao
     */
    private $app_dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var LastGeneratedClientSecretStore
     */
    private $last_created_app_store;
    /**
     * @var RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        AppDao $app_dao,
        SplitTokenVerificationStringHasher $hasher,
        LastGeneratedClientSecretStore $last_created_app_store,
        RedirectWithFeedbackFactory $redirector,
        \CSRFSynchronizerToken $csrf_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory       = $response_factory;
        $this->app_dao                = $app_dao;
        $this->hasher                 = $hasher;
        $this->last_created_app_store = $last_created_app_store;
        $this->redirector             = $redirector;
        $this->csrf_token             = $csrf_token;
    }

    public static function getProjectAdminURL(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin/add-app', $project->getID());
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project || $project === null);
        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        if ($this->isASiteAppCreation($project)) {
            $list_clients_url = SiteAdminListAppsController::URL;
        } else {
            $list_clients_url = ListAppsController::getUrl($project);
        }
        $this->csrf_token->check($list_clients_url);

        $parsed_body = $request->getParsedBody();
        if (
            ! is_array($parsed_body)
            || ! isset($parsed_body['name'])
            || ! isset($parsed_body['redirect_uri'])
        ) {
            return $this->redirectWithError($user, $list_clients_url);
        }
        $raw_app_name          = $parsed_body['name'];
        $raw_redirect_endpoint = $parsed_body['redirect_uri'];
        $use_pkce              = (bool) ($parsed_body['use_pkce'] ?? false);
        try {
            if ($this->isASiteAppCreation($project)) {
                $app_to_be_saved = NewOAuth2App::fromSiteAdministrationAppData(
                    $raw_app_name,
                    $raw_redirect_endpoint,
                    $use_pkce,
                    $this->hasher,
                    AppFactory::PLUGIN_APP
                );
            } else {
                $app_to_be_saved = NewOAuth2App::fromProjectAdministrationAppData(
                    $raw_app_name,
                    $raw_redirect_endpoint,
                    $use_pkce,
                    $project,
                    $this->hasher,
                    AppFactory::PLUGIN_APP
                );
            }
        } catch (InvalidAppDataException $e) {
            return $this->redirectWithError($user, $list_clients_url);
        }
        $app_id = $this->app_dao->create($app_to_be_saved);

        $this->last_created_app_store->storeLastGeneratedClientSecret($app_id, $app_to_be_saved->getSecret());

        return $this->response_factory->createResponse(302)->withHeader('Location', $list_clients_url);
    }

    private function redirectWithError(\PFUser $user, string $list_clients_url): ResponseInterface
    {
        return $this->redirector->createResponseForUser(
            $user,
            $list_clients_url,
            new NewFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-oauth2_server', 'The provided app data is not valid.')
            )
        );
    }

    /**
     * @psalm-assert-if-false \Project $project
     */
    private function isASiteAppCreation(?\Project $project): bool
    {
        return $project === null;
    }
}
