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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class AdminTabsCollectorTest extends TestCase
{
    public function testItReturnsDefaultTabs(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, '', 'default_url');

        $tabs = $collector->getTabs();
        self::assertEquals("Permissions", $tabs[0]->title);
        self::assertEquals("Properties", $tabs[1]->title);
        self::assertEquals("Obsolete Documents", $tabs[2]->title);
        self::assertEquals("Locked Documents", $tabs[3]->title);
    }

    public function testItAllowsToAddATabNearTheBeginning(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, '', 'default_url');

        $collector->addTabNearTheBeginning(
            new AdminTabPresenter("Lorem", "ipsum", "url", true)
        );

        $tabs = $collector->getTabs();
        self::assertEquals("Lorem", $tabs[0]->title);
        self::assertEquals("Permissions", $tabs[1]->title);
        self::assertEquals("Properties", $tabs[2]->title);
        self::assertEquals("Obsolete Documents", $tabs[3]->title);
        self::assertEquals("Locked Documents", $tabs[4]->title);
    }

    /**
     * @testWith ["admin_metadata"]
     *           ["admin_md_details"]
     *           ["admin_display_love"]
     *           ["admin_import_metadata_check"]
     */
    public function testItPreselectsPropertiesTab(string $current_view_identifier): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, $current_view_identifier, 'default_url');

        $tabs = $collector->getTabs();
        self::assertFalse($tabs[0]->is_active);
        self::assertTrue($tabs[1]->is_active);
        self::assertFalse($tabs[2]->is_active);
        self::assertFalse($tabs[3]->is_active);
    }

    public function testItPreselectsPermissionsTab(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, 'admin_permissions', 'default_url');

        $tabs = $collector->getTabs();
        self::assertTrue($tabs[0]->is_active);
        self::assertFalse($tabs[1]->is_active);
        self::assertFalse($tabs[2]->is_active);
        self::assertFalse($tabs[3]->is_active);
    }

    public function testItPreselectsObsoleteTab(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, 'admin_obsolete', 'default_url');

        $tabs = $collector->getTabs();
        self::assertFalse($tabs[0]->is_active);
        self::assertFalse($tabs[1]->is_active);
        self::assertTrue($tabs[2]->is_active);
        self::assertFalse($tabs[3]->is_active);
    }

    public function testItPreselectsLockTab(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $collector = new AdminTabsCollector($project, 'admin_lock_infos', 'default_url');

        $tabs = $collector->getTabs();
        self::assertFalse($tabs[0]->is_active);
        self::assertFalse($tabs[1]->is_active);
        self::assertFalse($tabs[2]->is_active);
        self::assertTrue($tabs[3]->is_active);
    }
}
