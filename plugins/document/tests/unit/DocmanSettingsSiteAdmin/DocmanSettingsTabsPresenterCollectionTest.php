<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\DocmanSettingsSiteAdmin;

final class DocmanSettingsTabsPresenterCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsAnArrayWithActiveTab(): void
    {
        $collection = new DocmanSettingsTabsPresenterCollection();

        $tabs = $collection->getTabs('/admin/document/files-upload-limits');
        self::assertEquals(
            [
                '/admin/document/files-upload-limits',
                '/admin/document/files-download-limits',
                '/admin/document/history-enforcement',
            ],
            array_map(
                static fn (DocmanSettingsTabPresenter $tab) => $tab->url,
                $tabs,
            )
        );
        self::assertEquals(
            [
                true,
                false,
                false,
            ],
            array_map(
                static fn (DocmanSettingsTabPresenter $tab) => $tab->is_active,
                $tabs,
            )
        );

        $tabs = $collection->getTabs('/admin/document/history-enforcement');
        self::assertEquals(
            [
                '/admin/document/files-upload-limits',
                '/admin/document/files-download-limits',
                '/admin/document/history-enforcement',
            ],
            array_map(
                static fn (DocmanSettingsTabPresenter $tab) => $tab->url,
                $tabs,
            )
        );
        self::assertEquals(
            [
                false,
                false,
                true,
            ],
            array_map(
                static fn (DocmanSettingsTabPresenter $tab) => $tab->is_active,
                $tabs,
            )
        );
    }
}
