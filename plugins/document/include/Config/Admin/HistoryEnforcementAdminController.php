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
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollectionBuilder;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class HistoryEnforcementAdminController implements DispatchableWithRequest
{
    public const URL = \DocmanPlugin::ADMIN_BASE_URL . '/history-enforcement';

    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    /**
     * @var HistoryEnforcementSettingsBuilder
     */
    private $settings_builder;

    /**
     * @var CSRFSynchronizerToken
     */
    private $token;

    /**
     * @var DocmanSettingsTabsPresenterCollectionBuilder
     */
    private $tabs_presenter_collection_builder;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        HistoryEnforcementSettingsBuilder $settings_builder,
        CSRFSynchronizerToken $token,
        DocmanSettingsTabsPresenterCollectionBuilder $tabs_presenter_collection_builder
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->settings_builder = $settings_builder;
        $this->token = $token;
        $this->tabs_presenter_collection_builder = $tabs_presenter_collection_builder;
    }

    public static function buildSelf(): self
    {
        return new self(
            new AdminPageRenderer(),
            new HistoryEnforcementSettingsBuilder(),
            new CSRFSynchronizerToken(self::URL),
            new DocmanSettingsTabsPresenterCollectionBuilder(\EventManager::instance())
        );
    }

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
                $this->tabs_presenter_collection_builder->build(),
                self::URL
            )
        );
    }
}
