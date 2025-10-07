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

use CSRFSynchronizerToken;
use HTTPRequest;
use Override;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\AI\Mistral\AuthenticationFailure;
use Tuleap\AI\Mistral\MistralConnectorLive;
use Tuleap\AI\Mistral\NoKeyFault;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\NeverThrow\Fault;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

final readonly class AISiteAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string ADMIN_SETTINGS_URL = '/ai/admin';

    public function __construct(
        private AdminPageRenderer $admin_page_renderer,
        private ProvideCurrentUser $current_user_provider,
    ) {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $this->current_user_provider->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = CSRFSynchronizerTokenPresenter::fromToken(self::getCSRFToken());

        $mistral_connector = new MistralConnectorLive(HttpClientFactory::createClientWithCustomTimeout(30));
        $presenter         = $mistral_connector->testConnection()->match(
            fn (): AISiteAdminPresenter => AISiteAdminPresenter::buildSuccess($csrf_token),
            function (Fault $fault) use ($csrf_token): AISiteAdminPresenter {
                return match ($fault::class) {
                    NoKeyFault::class => AISiteAdminPresenter::buildMissingKey($csrf_token),
                    AuthenticationFailure::class => AISiteAdminPresenter::buildAuthenticationFailure($csrf_token),
                    default => AISiteAdminPresenter::buildFailure($csrf_token, (string) $fault),
                };
            }
        );

        $this->admin_page_renderer->renderAPresenter(
            $presenter->getPageTitle(),
            __DIR__,
            $presenter->getTemplateName(),
            $presenter,
        );
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::ADMIN_SETTINGS_URL);
    }
}
