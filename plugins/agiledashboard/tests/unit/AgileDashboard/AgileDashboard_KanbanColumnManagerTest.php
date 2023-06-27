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

use Tuleap\Kanban\KanbanColumnDao;

class AgileDashboard_KanbanColumnManagerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $kanban_id;
    /**
     * @var int
     */
    private $tracker_id;
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
     * @var AgileDashboard_KanbanColumn
     */
    private $column;
    /**
     * @var KanbanColumnDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $column_dao;
    /**
     * @var AgileDashboard_KanbanActionsChecker|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $kanban_actions_checker;
    /**
     * @var AgileDashboard_Kanban
     */
    private $kanban;
    /**
     * @var AgileDashboard_KanbanColumnManager
     */
    private $kanban_column_manager;

    protected function setUp(): void
    {
        $this->kanban_id  = 2;
        $this->tracker_id = 4;
        $this->column_id  = 456;
        $this->wip_limit  = 12;

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getUserName')->andReturn('user name');
        $this->column = new AgileDashboard_KanbanColumn($this->column_id, $this->kanban_id, "Todo", true, null, 2, true);

        $this->column_dao             = \Mockery::spy(\Tuleap\Kanban\KanbanColumnDao::class);
        $this->kanban_actions_checker = \Mockery::spy(\AgileDashboard_KanbanActionsChecker::class);

        $this->kanban                = new AgileDashboard_Kanban($this->kanban_id, $this->tracker_id, "My Kanban");
        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
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
        $this->kanban_actions_checker->shouldReceive('checkUserCanAdministrate')->with($this->user, $this->kanban)->andThrows(new AgileDashboard_UserNotAdminException($this->user));

        $this->column_dao->shouldReceive('setColumnWipLimit')->with($this->kanban_id, $this->column_id, $this->wip_limit)->never();
        $this->expectException(\AgileDashboard_UserNotAdminException::class);

        $this->kanban_column_manager->updateWipLimit($this->user, $this->kanban, $this->column, $this->wip_limit);
    }
}
