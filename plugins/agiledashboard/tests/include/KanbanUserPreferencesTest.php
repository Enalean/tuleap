<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class AgileDashboard_KanbanUserPreferencesTest extends TuleapTestCase
{

    /** @var PFUser */
    private $user;

    /** @var AgileDashboard_KanbanUserPreferences */
    private $user_preferences;

    /** @var AgileDashboard_Kanban */
    private $kanban;

    private $column_id = 10;

    public function setUp()
    {
        parent::setUp();
        $this->user = mock('PFUser');

        $this->user_preferences = new AgileDashboard_KanbanUserPreferences();
        $this->kanban = new AgileDashboard_Kanban(1, 1, 'My first kanban');
    }

    public function testDefaultBehavior()
    {
        $this->assertFalse($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
        $this->assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
        $this->assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function itOpensTheBacklog()
    {
        stub($this->user)
            ->getPreference('kanban_collapse_backlog_1')
            ->returns(AgileDashboard_KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function itOpensTheArchive()
    {
        stub($this->user)
            ->getPreference('kanban_collapse_archive_1')
            ->returns(AgileDashboard_KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function itOpensTheColumn()
    {
        stub($this->user)
            ->getPreference('kanban_collapse_column_1_10')
            ->returns(AgileDashboard_KanbanUserPreferences::EXPAND);

        $this->assertTrue($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }

    public function itClosesTheBacklog()
    {
        stub($this->user)
                ->getPreference('kanban_collapse_backlog_1')
                ->returns(AgileDashboard_KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isBacklogOpen($this->kanban, $this->user));
    }

    public function itClosesTheArchive()
    {
        stub($this->user)
            ->getPreference('kanban_collapse_archive_1')
            ->returns(AgileDashboard_KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isArchiveOpen($this->kanban, $this->user));
    }

    public function itClosesTheColumn()
    {
        stub($this->user)
            ->getPreference('kanban_collapse_column_1_10')
            ->returns(AgileDashboard_KanbanUserPreferences::COLLAPSE);

        $this->assertFalse($this->user_preferences->isColumnOpen($this->kanban, $this->column_id, $this->user));
    }
}
