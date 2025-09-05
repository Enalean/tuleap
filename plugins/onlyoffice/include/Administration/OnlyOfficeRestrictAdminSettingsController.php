<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use CSRFSynchronizerToken;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\DB\UUID;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\OnlyOffice\DocumentServer\IRestrictDocumentServer;
use Tuleap\OnlyOffice\DocumentServer\IRetrieveDocumentServers;
use Tuleap\OnlyOffice\DocumentServer\RestrictedProject;
use Tuleap\OnlyOffice\DocumentServer\TooManyServersException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class OnlyOfficeRestrictAdminSettingsController extends DispatchablePSR15Compatible
{
    public const URL = OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL . '/restrict';

    public function __construct(
        private CSRFSynchronizerToken $csrf_token,
        private IRetrieveDocumentServers $retriever,
        private IRestrictDocumentServer $restrictor,
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->csrf_token->check();

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        $server_id = (string) $request->getAttribute('id');
        try {
            $server = $this->retriever->retrieveById($server_id);
        } catch (DocumentServerNotFoundException) {
            throw new NotFoundException();
        }

        $body = $request->getParsedBody();
        if (! isset($body['is_restricted'])) {
            throw new ForbiddenException();
        }

        return match ((bool) $body['is_restricted']) {
            true => $this->restrict($server, $user, $body),
            false => $this->unrestrict($server, $user),
        };
    }

    private function unrestrict(DocumentServer $server, \PFUser $user): ResponseInterface
    {
        try {
            $this->restrictor->unrestrict($server->id);

            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                self::getServerRestrictUrl($server->id),
                new NewFeedback(
                    \Feedback::SUCCESS,
                    dgettext('tuleap-onlyoffice', 'Document server restrictions have been removed')
                ),
            );
        } catch (TooManyServersException) {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL,
                new NewFeedback(
                    \Feedback::ERROR,
                    dgettext('tuleap-onlyoffice', 'Document server restrictions cannot be removed since there are more than one server defined. Please remove other servers beforehand.')
                ),
            );
        }
    }

    private function restrict(DocumentServer $server, \PFUser $user, array $body): ResponseInterface
    {
        if (isset($body['project-to-add'])) {
            if (! is_numeric($body['project-to-add'])) {
                throw new ForbiddenException();
            }

            $new_restricted_projects = [
                ...array_map(static fn (RestrictedProject $project) => $project->id, $server->project_restrictions),
                (int) $body['project-to-add'],
            ];
            $this->restrictor->restrict($server->id, $new_restricted_projects);
        } elseif (isset($body['projects-to-remove'])) {
            if (! is_array($body['projects-to-remove'])) {
                throw new ForbiddenException();
            }

            $new_restricted_projects = array_diff(
                array_map(static fn (RestrictedProject $project) => $project->id, $server->project_restrictions),
                $body['projects-to-remove'],
            );
            $this->restrictor->restrict($server->id, $new_restricted_projects);
        } else {
            $this->restrictor->restrict($server->id, []);
        }

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            self::getServerRestrictUrl($server->id),
            new NewFeedback(\Feedback::SUCCESS, dgettext('tuleap-onlyoffice', 'Document server restrictions have been saved')),
        );
    }

    public static function getServerRestrictUrl(UUID $server_id): string
    {
        return self::URL . '/' . urlencode($server_id->toString());
    }
}
