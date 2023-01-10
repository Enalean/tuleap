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
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OnlyOffice\DocumentServer\IRestrictDocumentServer;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class OnlyOfficeRestrictAdminSettingsController extends DispatchablePSR15Compatible
{
    public const URL = OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL . '/restrict';

    public function __construct(
        private CSRFSynchronizerToken $csrf_token,
        private IRestrictDocumentServer $restrictor,
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->csrf_token->check();

        $body     = $request->getParsedBody();
        $projects = $body['projects'] ?? null;
        if (! is_array($projects)) {
            throw new ForbiddenException();
        }

        $server_id = (int) $request->getAttribute('id');
        $this->restrictor->restrict($server_id, $projects);

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            self::getServerRestrictUrl($server_id),
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-onlyoffice', 'Document server restrictions have been saved')),
        );
    }

    public static function getServerRestrictUrl(int $server_id): string
    {
        return self::URL . '/' . $server_id;
    }
}
