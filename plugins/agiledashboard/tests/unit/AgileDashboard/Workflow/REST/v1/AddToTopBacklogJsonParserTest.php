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

namespace Tuleap\AgileDashboard\Workflow\REST\v1;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogJsonParserTest extends TestCase
{
    private AddToTopBacklogJsonParser $parser;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;

    protected function setUp(): void
    {
        $this->explicit_backlog_dao = $this->createMock(ExplicitBacklogDao::class);
        $this->parser               = new AddToTopBacklogJsonParser($this->explicit_backlog_dao);
    }

    public function testAcceptReturnsTrueIfJsonIsAddToTopBacklog(): void
    {
        $json_post_action = [
            'id'   => 4,
            'type' => 'add_to_top_backlog',
        ];

        self::assertTrue($this->parser->accept($json_post_action));
    }

    public function testAcceptReturnsFalseIfJsonIsNotAddToTopBacklog(): void
    {
        $json_post_action = [
            'id'   => 4,
            'type' => 'whatever',
        ];

        self::assertFalse($this->parser->accept($json_post_action));
    }

    public function testAcceptReturnsFalseIfJsonIsNotTypeKeyProvided(): void
    {
        $json_post_action = [
            'id'   => 4,
            'type' => 'whatever',
        ];

        self::assertFalse($this->parser->accept($json_post_action));
    }

    public function testItThrowsAnExceptionIfProjectDoesNotUseExplicitBacklog(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn($tracker);

        $json_post_action = [
            'id'   => 4,
            'type' => 'add_to_top_backlog',
        ];

        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        self::expectException(I18NRestException::class);

        $this->parser->parse($workflow, $json_post_action);
    }

    public function testItReturnsTheAddToTopBacklogValue(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn($tracker);

        $json_post_action = [
            'id'   => 4,
            'type' => 'add_to_top_backlog',
        ];

        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $value_object = $this->parser->parse($workflow, $json_post_action);

        self::assertInstanceOf(AddToTopBacklogValue::class, $value_object);
    }
}
