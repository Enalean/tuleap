<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AI\SiteAdmin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\Config\ConfigUpdater;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchablePSR15Compatible;

final class AISiteAdminUpdateSettingsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly CSRFSynchronizerTokenInterface $csrf_token,
        private readonly ConfigUpdater $config_set,
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
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

        $body = $request->getParsedBody();
        if (! isset($body[AISiteAdminPresenter::API_KEY_INPUT]) || trim($body[AISiteAdminPresenter::API_KEY_INPUT]) === '') {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                AISiteAdminController::ADMIN_SETTINGS_URL,
                new NewFeedback(\Feedback::ERROR, dgettext('tuleap-ai', 'API key is empty')),
            );
        }

        $this->config_set->set(
            MistralConnector::CONFIG_API_KEY,
            new ConcealedString($body[AISiteAdminPresenter::API_KEY_INPUT])
        );

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            AISiteAdminController::ADMIN_SETTINGS_URL,
            new NewFeedback(\Feedback::SUCCESS, dgettext('tuleap-ai', 'API key has been saved')),
        );
    }
}
