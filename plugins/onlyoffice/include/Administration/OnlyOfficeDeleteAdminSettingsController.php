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
use Tuleap\OnlyOffice\DocumentServer\IDeleteDocumentServer;
use Tuleap\Request\DispatchablePSR15Compatible;

final class OnlyOfficeDeleteAdminSettingsController extends DispatchablePSR15Compatible
{
    public const URL = OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL . '/delete';

    public function __construct(
        private CSRFSynchronizerToken $csrf_token,
        private IDeleteDocumentServer $deletor,
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

        $this->deletor->delete((string) $request->getAttribute('id'));

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-onlyoffice', 'Document server have been deleted')),
        );
    }
}
