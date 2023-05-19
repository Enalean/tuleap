<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Project\MappingRegistry;
use Tuleap\Roadmap\Widget\RoadmapWidgetPresenter;
use Tuleap\Roadmap\Widget\RoadmapWidgetPresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class RoadmapProjectWidgetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const WIDGET_CONTENT_ID = 13;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var RoadmapProjectWidget
     */
    private $widget;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RoadmapWidgetPresenterBuilder
     */
    private $presenter_builder;

    protected function setUp(): void
    {
        $this->dao = Mockery::mock(RoadmapWidgetDao::class);

        $template_render = new class extends \TemplateRenderer {
            public function renderToString($template_name, $presenter): string
            {
                return '';
            }
        };

        $this->presenter_builder = $this->createMock(RoadmapWidgetPresenterBuilder::class);

        $this->widget = new RoadmapProjectWidget(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $template_render,
            $this->presenter_builder,
            Mockery::mock(\TrackerFactory::class),
            new FilterReportDao(),
        );
    }

    protected function tearDown(): void
    {
        \UserManager::clearInstance();
    }

    public function testItDoesNotComplainAboutUninitializedIterationsTrackersId(): void
    {
        $user_manager = $this->createMock(\UserManager::class);
        \UserManager::setInstance($user_manager);

        $user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::anActiveUser()->build());

        $this->presenter_builder
            ->expects(self::once())
            ->method('getPresenter')
            ->willReturn(new RoadmapWidgetPresenter(123, [], false, false, "month"));

        $this->widget->getContent();
    }

    public function testCloneContentBlindlyCloneContentIfNoTrackerMapping(): void
    {
        $this->dao
            ->shouldReceive('cloneContent')
            ->with(42, 102, "g")
            ->once();

        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            new MappingRegistry([])
        );
    }

    public function testCloneContentBlindlyCloneContentIfContentIdCannotBeFound(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([]);

        $this->dao
            ->shouldReceive('cloneContent')
            ->with(42, 102, "g")
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [111 => 222]);
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }

    public function testCloneContentTakeThePreviousTrackerIdIfItIsNotPartOfTheMapping(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([
                'title'                     => 'Roadmap',
                'lvl1_iteration_tracker_id' => 120,
                'lvl2_iteration_tracker_id' => 130,
                'default_timescale'         => 'week',
            ]);
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(42)
            ->once()
            ->andReturn([110]);

        $this->dao
            ->shouldReceive('insertContent')
            ->with(102, "g", 'Roadmap', [110], 0, 'week', 120, 130)
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [111 => 222]);
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }

    public function testCloneContentTakeTheTrackerIdFromTheMapping(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([
                'title'                     => 'Roadmap',
                'lvl1_iteration_tracker_id' => 121,
                'lvl2_iteration_tracker_id' => 131,
                'default_timescale'         => 'week',
            ]);
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(42)
            ->once()
            ->andReturn([111]);

        $this->dao
            ->shouldReceive('insertContent')
            ->with(102, "g", 'Roadmap', [1110], 0, 'week', 1210, 1310)
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(
            \TrackerFactory::TRACKER_MAPPING_KEY,
            [111 => 1110, 121 => 1210, 131 => 1310]
        );
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }
}
