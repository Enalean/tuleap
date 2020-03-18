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

namespace Tuleap\OAuth2Server\User\Account;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\User\AuthorizationRevoker;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;

final class AppRevocationController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    public const  URL    = '/plugins/oauth2_server/account/apps/revoke';
    private const APP_ID = 'app_id';
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AuthorizationRevoker
     */
    private $authorization_revoker;
    /**
     * @var RedirectWithFeedbackFactory
     */
    private $redirector;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        \CSRFSynchronizerToken $csrf_token,
        \UserManager $user_manager,
        AuthorizationRevoker $authorization_revoker,
        RedirectWithFeedbackFactory $redirector,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory      = $response_factory;
        $this->csrf_token            = $csrf_token;
        $this->user_manager          = $user_manager;
        $this->authorization_revoker = $authorization_revoker;
        $this->redirector            = $redirector;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);

        $this->csrf_token->check(AccountAppsController::URL);

        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            return $this->redirectToAccountAppsList();
        }
        $body_params = $request->getParsedBody();
        if (! is_array($body_params) || ! isset($body_params[self::APP_ID])) {
            return $this->redirector->createResponseForUser(
                $user,
                AccountAppsController::URL,
                new NewFeedback(\Feedback::ERROR, dgettext('tuleap-oauth2_server', "The App's ID is required."))
            );
        }
        $app_id = (int) $body_params[self::APP_ID];
        if (! $this->authorization_revoker->doesAuthorizationExist($user, $app_id)) {
            return $this->redirector->createResponseForUser(
                $user,
                AccountAppsController::URL,
                new NewFeedback(
                    \Feedback::ERROR,
                    dgettext('tuleap-oauth2_server', 'Authorization not found for this App, aborting revocation.')
                )
            );
        }
        $this->authorization_revoker->revokeAppAuthorization($user, $app_id);

        return $this->redirector->createResponseForUser(
            $user,
            AccountAppsController::URL,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-oauth2_server', 'Access has been revoked.'))
        );
    }

    private function redirectToAccountAppsList(): ResponseInterface
    {
        return $this->response_factory->createResponse(302)
            ->withHeader('Location', AccountAppsController::URL);
    }
}
