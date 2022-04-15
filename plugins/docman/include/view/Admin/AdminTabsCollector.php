<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\View\Admin;

use Tuleap\Docman\View\DocmanViewURLBuilder;
use Tuleap\Event\Dispatchable;

final class AdminTabsCollector implements Dispatchable
{
    public const NAME = "adminTabsCollector";

    /**
     * @var AdminTabPresenter[]
     */
    private array $tabs = [];

    public function __construct(
        private \Project $project,
        private string $current_view_identifier,
        private string $default_url,
    ) {
    }

    public function getCurrentViewIdentifier(): string
    {
        return $this->current_view_identifier;
    }

    /**
     * @return AdminTabPresenter[]
     */
    public function getTabs(): array
    {
        return array_merge(
            $this->tabs,
            [
                new AdminTabPresenter(
                    \Docman_View_Admin_Permissions::getTabTitle(),
                    \Docman_View_Admin_Permissions::getTabDescription(),
                    DocmanViewURLBuilder::buildUrl(
                        $this->default_url,
                        ['action' => \Docman_View_Admin_Permissions::IDENTIFIER],
                        false,
                    ),
                    $this->current_view_identifier === \Docman_View_Admin_Permissions::IDENTIFIER,
                ),
                new AdminTabPresenter(
                    \Docman_View_Admin_Metadata::getTabTitle(),
                    \Docman_View_Admin_Metadata::getTabDescription(),
                    DocmanViewURLBuilder::buildUrl(
                        $this->default_url,
                        ['action' => \Docman_View_Admin_Metadata::IDENTIFIER],
                        false,
                    ),
                    in_array(
                        $this->current_view_identifier,
                        [
                            \Docman_View_Admin_Metadata::IDENTIFIER,
                            \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                            \Docman_View_Admin_MetadataDetailsUpdateLove::IDENTIFIER,
                            \Docman_View_Admin_MetadataImport::IDENTIFIER,
                        ],
                        true
                    ),
                ),
                new AdminTabPresenter(
                    \Docman_View_Admin_Obsolete::getTabTitle(),
                    \Docman_View_Admin_Obsolete::getTabDescription(),
                    DocmanViewURLBuilder::buildUrl(
                        $this->default_url,
                        ['action' => \Docman_View_Admin_Obsolete::IDENTIFIER],
                        false,
                    ),
                    $this->current_view_identifier === \Docman_View_Admin_Obsolete::IDENTIFIER,
                ),
                new AdminTabPresenter(
                    \Docman_View_Admin_LockInfos::getTabTitle(),
                    \Docman_View_Admin_LockInfos::getTabDescription(),
                    DocmanViewURLBuilder::buildUrl(
                        $this->default_url,
                        ['action' => \Docman_View_Admin_LockInfos::IDENTIFIER],
                        false,
                    ),
                    $this->current_view_identifier === \Docman_View_Admin_LockInfos::IDENTIFIER,
                ),
            ]
        );
    }

    public function addTabNearTheBeginning(AdminTabPresenter $tab): void
    {
        $this->tabs[] = $tab;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }
}
