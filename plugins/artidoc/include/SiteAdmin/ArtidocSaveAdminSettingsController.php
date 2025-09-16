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
 */

declare(strict_types=1);

namespace Tuleap\Artidoc\SiteAdmin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Config\ConfigUpdater;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchablePSR15Compatible;

final class ArtidocSaveAdminSettingsController extends DispatchablePSR15Compatible
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

        $body                      = $request->getParsedBody();
        $can_user_display_versions = '1' === ($body['can_user_display_versions'] ?? '0');

        $this->config_set->set(
            \ForgeConfig::FEATURE_FLAG_PREFIX . ArtidocAdminSettings::FEATURE_FLAG_VERSIONS,
            $can_user_display_versions ? '1' : '0'
        );

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            ArtidocAdminSettingsController::ADMIN_SETTINGS_URL,
            new NewFeedback(\Feedback::INFO, dgettext('tuleap-artidoc', 'Artidoc settings have been saved')),
        );
    }
}
