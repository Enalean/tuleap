<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollection;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class HistoryEnforcementAdminController implements DispatchableWithRequest
{
    public const string URL = \DocmanPlugin::ADMIN_BASE_URL . '/history-enforcement';


    public function __construct(
        private AdminPageRenderer $admin_page_renderer,
        private HistoryEnforcementSettingsBuilder $settings_builder,
        private CSRFSynchronizerToken $token,
    ) {
    }

    public static function buildSelf(): self
    {
        return new self(
            new AdminPageRenderer(),
            new HistoryEnforcementSettingsBuilder(),
            new CSRFSynchronizerToken(self::URL),
        );
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->renderANoFramedPresenter(
            \dgettext('tuleap-docman', 'Document settings'),
            __DIR__ . '/../../../templates',
            'history-enforcement',
            new HistoryEnforcementPresenter(
                $this->token,
                $this->settings_builder->build(),
                new DocmanSettingsTabsPresenterCollection(),
                self::URL
            )
        );
    }
}
