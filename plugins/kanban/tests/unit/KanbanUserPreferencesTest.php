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

use Mockery;
use PFUser;

//phpcs:ignore: PSR1.Classes.ClassDeclaration.MissingNamespace
final class KanbanUserPreferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var PFUser */
    private $user;

    /** @var KanbanUserPreferences */
    private $user_preferences;

    /** @var Kanban */
    private $kanban;

    private $column_id = 10;

    protected function setUp(): void
    {
        $this->user = Mockery::spy(\PFUser::class);

        $this->user_preferences = new KanbanUserPreferences();
        $this->kanban           = new Kanban(1, 1, 'My first kanban');
    }

    public function testDefaultBehavior(): void
    {
        $this->assertFalse($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
        $this->assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
        $this->assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function testItOpensTheBacklog(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_backlog_1')->andReturns(KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItOpensTheBacklogIfNoPreferenceSet(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_backlog_1')->andReturnFalse();

        $this->assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItOpensTheArchive(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_archive_1')->andReturns(KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function testItOpensTheColumn(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_column_1_10')->andReturns(KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function testItClosesTheBacklog(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_backlog_1')->andReturns(KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function testItClosesTheArchive(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_archive_1')->andReturns(KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function testItClosesTheColumn(): void
    {
        $this->user->shouldReceive('getPreference')->with('kanban_collapse_column_1_10')->andReturns(KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }
}
