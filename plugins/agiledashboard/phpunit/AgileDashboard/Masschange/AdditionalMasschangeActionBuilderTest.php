<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use TemplateRenderer;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;

final class AdditionalMasschangeActionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AdditionalMasschangeActionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRenderer
     */
    private $template_renderer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $root_planning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = Mockery::mock(ExplicitBacklogDao::class);
        $this->planning_factory     = Mockery::mock(PlanningFactory::class);
        $this->template_renderer    = Mockery::mock(TemplateRenderer::class);

        $this->builder = new AdditionalMasschangeActionBuilder(
            $this->explicit_backlog_dao,
            $this->planning_factory,
            $this->template_renderer
        );

        $this->tracker = Mockery::mock(Tracker::class);
        $this->user    = Mockery::mock(PFUser::class);

        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
        $this->tracker->shouldReceive('getProject')->andReturn($project);
        $this->tracker->shouldReceive('getId')->andReturn('149');

        $this->root_planning = Mockery::mock(Planning::class);
    }

    public function testItRendersTheMasschangeAdditionalAction()
    {
        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->root_planning->shouldReceive('getBacklogTrackersIds')
            ->once()
            ->andReturn([149]);

        $this->template_renderer->shouldReceive('renderToString')->once()->andReturn('whatever');

        $additional_action = $this->builder->buildMasschangeAction($this->tracker, $this->user);

        $this->assertNotNull($additional_action);
    }

    public function testItReturnsNullIfUserIsNotTrackerAdmin(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnFalse();

        $this->template_renderer->shouldNotReceive('renderToString');

        $additional_action = $this->builder->buildMasschangeAction($this->tracker, $this->user);
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfProjectDoesNotUseExplicitBacklog()
    {
        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnFalse();

        $this->template_renderer->shouldNotReceive('renderToString');

        $additional_action = $this->builder->buildMasschangeAction($this->tracker, $this->user);
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfProjectDoesNotHaveARootPlanning()
    {
        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturnNull();

        $this->template_renderer->shouldNotReceive('renderToString');

        $additional_action = $this->builder->buildMasschangeAction($this->tracker, $this->user);
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfTrackerNotABacklogTracker()
    {
        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->root_planning->shouldReceive('getBacklogTrackersIds')
            ->once()
            ->andReturn([172]);

        $this->template_renderer->shouldNotReceive('renderToString');

        $additional_action = $this->builder->buildMasschangeAction($this->tracker, $this->user);
        $this->assertNull($additional_action);
    }
}
