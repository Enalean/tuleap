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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\OAuth2AppRemover;
use Tuleap\Request\DispatchablePSR15Compatible;

final class DeleteAppController extends DispatchablePSR15Compatible
{
    /**
     * @var RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var OAuth2AppRemover
     */
    private $app_remover;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        RedirectWithFeedbackFactory $redirector,
        OAuth2AppRemover $app_remover,
        \CSRFSynchronizerToken $csrf_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->redirector  = $redirector;
        $this->app_remover = $app_remover;
        $this->csrf_token  = $csrf_token;
    }

    public static function getUrl(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin/delete-app', $project->getID());
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
        if (! is_array($parsed_body) || ! isset($parsed_body['app_id']) || ! is_numeric($parsed_body['app_id'])) {
            return $this->redirector->createResponseForUser(
                $user,
                $list_clients_url,
                new NewFeedback(\Feedback::ERROR, dgettext('tuleap-oauth2_server', "The App's ID is required."))
            );
        }

        $this->app_remover->deleteAppByID((int) $parsed_body['app_id']);

        return $this->redirector->createResponseForUser(
            $user,
            $list_clients_url,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-oauth2_server', 'The App has been successfully deleted.'))
        );
    }
}
