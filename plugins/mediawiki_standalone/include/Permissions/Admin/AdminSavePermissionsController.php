<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

class AdminSavePermissionsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private ProjectPermissionsSaver $permissions_saver,
        private UserGroupToSaveRetriever $user_group_to_save_retriever,
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private CSRFSynchronizerTokenProvider $token_provider,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $this->token_provider->getCSRF($project)->check();

        try {
            $permissions = PermissionsFromRequestExtractor::extractPermissionsFromRequest($request)->getPermissions();
            $this->permissions_saver->save(
                $project,
                $this->user_group_to_save_retriever->getUserGroups($project, $permissions->readers),
                $this->user_group_to_save_retriever->getUserGroups($project, $permissions->writers),
                $this->user_group_to_save_retriever->getUserGroups($project, $permissions->admins),
            );
        } catch (InvalidRequestException | UnknownUserGroupException $exception) {
            throw new ForbiddenException();
        }

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            AdminPermissionsController::getAdminUrl($project),
            new NewFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki permissions have been saved')
            ),
        );
    }
}
