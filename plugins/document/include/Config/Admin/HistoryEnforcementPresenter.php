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
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabPresenter;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollection;
use Tuleap\Document\Config\HistoryEnforcementSettings;

class HistoryEnforcementPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var bool
     */
    public $is_changelog_proposed;

    /**
     * @var DocmanSettingsTabPresenter[]
     */
    public $tabs;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        HistoryEnforcementSettings $settings,
        DocmanSettingsTabsPresenterCollection $tabs_collection,
        string $current_url
    ) {
        $this->csrf_token            = $csrf_token;
        $this->is_changelog_proposed = $settings->isChangelogProposedAfterDragAndDrop();
        $this->tabs                  = $tabs_collection->getTabs($current_url);
    }
}
