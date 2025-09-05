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
use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OnlyOffice\DocumentServer\ICreateDocumentServer;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class OnlyOfficeCreateAdminSettingsController extends DispatchablePSR15Compatible
{
    public const URL = OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL . '/create';

    public function __construct(
        private CSRFSynchronizerToken $csrf_token,
        private ICreateDocumentServer $creator,
        private OnlyOfficeServerUrlValidator $server_url_validator,
        private OnlyOfficeSecretKeyValidator $secret_key_validator,
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

        $body       = $request->getParsedBody();
        $server_url = (string) ($body['server_url'] ?? '');
        $server_key = new ConcealedString((string) ($body['server_key'] ?? ''));

        try {
            $this->server_url_validator->checkIsValid($server_url);
            $this->secret_key_validator->checkIsValid($server_key);
        } catch (InvalidConfigKeyValueException $exception) {
            throw new ForbiddenException();
        }

        $this->creator->create($server_url, $server_key);

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-onlyoffice', 'Document server has been created')),
        );
    }
}
