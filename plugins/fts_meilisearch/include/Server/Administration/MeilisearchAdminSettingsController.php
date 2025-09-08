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

namespace Tuleap\FullTextSearchMeilisearch\Server\Administration;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\FullTextSearchMeilisearch\Server\IProvideCurrentKeyForLocalServer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

final class MeilisearchAdminSettingsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const ADMIN_SETTINGS_URL = '/meilisearch/admin';

    public function __construct(
        private IProvideCurrentKeyForLocalServer $local_meilisearch_server,
        private AdminPageRenderer $admin_page_renderer,
        private ProvideCurrentUser $current_user_provider,
        private MeilisearchAdminSettingsPresenter $admin_settings_presenter,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $key_local_meilisearch_server = $this->local_meilisearch_server->getCurrentKey();
        $is_using_local_server        = $key_local_meilisearch_server !== null;
        if ($is_using_local_server) {
            throw new ForbiddenException('Cannot set url nor api key for a local meilisearch server');
        }

        $current_user = $this->current_user_provider->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-fts_meilisearch', 'Meilisearch server settings'),
            __DIR__ . '/../../../templates/',
            'site-admin',
            $this->admin_settings_presenter
        );
    }
}
