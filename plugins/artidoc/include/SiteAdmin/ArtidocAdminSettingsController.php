<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

use HTTPRequest;
use Override;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

final readonly class ArtidocAdminSettingsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string ADMIN_SETTINGS_URL = '/artidoc/admin';

    public function __construct(
        private AdminPageRenderer $admin_page_renderer,
        private ProvideCurrentUser $current_user_provider,
        private ArtidocAdminSettingsPresenter $admin_settings_presenter,
    ) {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $this->current_user_provider->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-artidoc', 'Artidoc settings'),
            __DIR__,
            'site-admin',
            $this->admin_settings_presenter
        );
    }
}
