<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

//phpcs:ignore: PSR1.Classes.ClassDeclaration.MissingNamespace
final class KanbanUserPreferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var  */
    private PFUser&MockObject $user;

    /** @var  */
    private KanbanUserPreferences $user_preferences;

    /** @var Kanban */
    private $kanban;

    private int $column_id = 10;

    protected function setUp(): void
    {
        $this->user = $this->createMock(\PFUser::class);

        $this->user_preferences = new KanbanUserPreferences();
        $this->kanban           = new Kanban(1, TrackerTestBuilder::aTracker()->build(), false, 'My first kanban');
    }

    public function testDefaultBehavior(): void
    {
        $this->user->method('getPreference')->willReturn(false);

        self::assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
        self::assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
        self::assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function testItOpensTheBacklog(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_backlog_1')->willReturn(KanbanUserPreferences::EXPAND);

        self::assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItOpensTheBacklogIfNoPreferenceSet(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_backlog_1')->willReturn(false);

        self::assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItOpensTheArchive(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_archive_1')->willReturn(KanbanUserPreferences::EXPAND);

        self::assertTrue($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function testItOpensTheColumn(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_column_1_10')->willReturn(KanbanUserPreferences::EXPAND);

        self::assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function testItClosesTheBacklog(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_backlog_1')->willReturn(KanbanUserPreferences::COLLAPSE);

        self::assertFalse($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItClosesTheArchive(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_archive_1')->willReturn(KanbanUserPreferences::COLLAPSE);

        self::assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function testItClosesTheColumn(): void
    {
        $this->user->method('getPreference')->with('kanban_collapse_column_1_10')->willReturn(KanbanUserPreferences::COLLAPSE);

        self::assertFalse($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }
}
