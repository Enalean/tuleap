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

declare(strict_types=1);

namespace Tuleap\Kanban;

use Mockery;
use PFUser;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class KanbanColumnManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $kanban_id;
    /**
     * @var int
     */
    private $column_id;
    /**
     * @var int
     */
    private $wip_limit;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var KanbanColumn
     */
    private $column;
    /**
     * @var KanbanColumnDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $column_dao;
    /**
     * @var KanbanActionsChecker|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $kanban_actions_checker;
    /**
     * @var Kanban
     */
    private $kanban;
    /**
     * @var KanbanColumnManager
     */
    private $kanban_column_manager;

    protected function setUp(): void
    {
        $this->kanban_id = 2;
        $this->column_id = 456;
        $this->wip_limit = 12;

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getUserName')->andReturn('user name');
        $this->column = new KanbanColumn($this->column_id, $this->kanban_id, "Todo", true, 2, true);

        $this->column_dao             = \Mockery::spy(\Tuleap\Kanban\KanbanColumnDao::class);
        $this->kanban_actions_checker = \Mockery::spy(\Tuleap\Kanban\KanbanActionsChecker::class);

        $this->kanban                = new Kanban($this->kanban_id, TrackerTestBuilder::aTracker()->build(), "My Kanban");
        $this->kanban_column_manager = new KanbanColumnManager(
            $this->column_dao,
            \Mockery::spy(
                \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao::class
            ),
            $this->kanban_actions_checker
        );
    }

    public function testItUpdatesTheWIPLimit(): void
    {
        $this->column_dao->shouldReceive('setColumnWipLimit')->with($this->kanban_id, $this->column_id, $this->wip_limit)->once();

        $this->kanban_column_manager->updateWipLimit($this->user, $this->kanban, $this->column, $this->wip_limit);
    }

    public function testItThrowsAnExceptionIfUserNotAdmin(): void
    {
        $this->kanban_actions_checker->shouldReceive('checkUserCanAdministrate')->with($this->user, $this->kanban)->andThrows(new KanbanUserNotAdminException($this->user));

        $this->column_dao->shouldReceive('setColumnWipLimit')->with($this->kanban_id, $this->column_id, $this->wip_limit)->never();
        $this->expectException(\Tuleap\Kanban\KanbanUserNotAdminException::class);

        $this->kanban_column_manager->updateWipLimit($this->user, $this->kanban, $this->column, $this->wip_limit);
    }
}
