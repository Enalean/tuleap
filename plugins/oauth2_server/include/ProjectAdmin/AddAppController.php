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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\InvalidAppDataException;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\NewOAuth2App;
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
     * @var LastCreatedOAuth2AppStore
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
        LastCreatedOAuth2AppStore $last_created_app_store,
        RedirectWithFeedbackFactory $redirector,
        \CSRFSynchronizerToken $csrf_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory       = $response_factory;
        $this->app_dao                = $app_dao;
        $this->hasher                 = $hasher;
        $this->last_created_app_store = $last_created_app_store;
        $this->redirector             = $redirector;
        $this->csrf_token             = $csrf_token;
    }

    public static function getUrl(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin/add-app', $project->getID());
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);
        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        $list_clients_url = ListAppsController::getUrl($project);
        $this->csrf_token->check($list_clients_url);

        $parsed_body = $request->getParsedBody();
        if (! is_array($parsed_body)
            || ! isset($parsed_body['name'])
            || ! isset($parsed_body['redirect_uri'])
        ) {
            return $this->redirectWithError($user, $list_clients_url);
        }
        $raw_app_name          = $parsed_body['name'];
        $raw_redirect_endpoint = $parsed_body['redirect_uri'];
        $use_pkce              = (bool) ($parsed_body['use_pkce'] ?? false);
        try {
            $app_to_be_saved = NewOAuth2App::fromAppData(
                $raw_app_name,
                $raw_redirect_endpoint,
                $use_pkce,
                $project,
                $this->hasher
            );
        } catch (InvalidAppDataException $e) {
            return $this->redirectWithError($user, $list_clients_url);
        }
        $app_id = $this->app_dao->create($app_to_be_saved);

        $this->last_created_app_store->storeLastCreatedApp($app_id, $app_to_be_saved);

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
}
