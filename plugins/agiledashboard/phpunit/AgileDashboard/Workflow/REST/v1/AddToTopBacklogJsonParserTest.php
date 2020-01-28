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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\REST\I18NRestException;
use Workflow;

class AddToTopBacklogJsonParserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AddToTopBacklogJsonParser
     */
    private $parser;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = Mockery::mock(ExplicitBacklogDao::class);

        $this->parser = new AddToTopBacklogJsonParser(
            $this->explicit_backlog_dao
        );
    }

    public function testAcceptReturnsTrueIfJsonIsAddToTopBacklog(): void
    {
        $json_post_action = [
            'id' => 4,
            'type' => 'add_to_top_backlog'
        ];

        $this->assertTrue($this->parser->accept($json_post_action));
    }

    public function testAcceptReturnsFalseIfJsonIsNotAddToTopBacklog(): void
    {
        $json_post_action = [
            'id' => 4,
            'type' => 'whatever'
        ];

        $this->assertFalse($this->parser->accept($json_post_action));
    }

    public function testAcceptReturnsFalseIfJsonIsNotTypeKeyProvided(): void
    {
        $json_post_action = [
            'id' => 4,
            'type' => 'whatever'
        ];

        $this->assertFalse($this->parser->accept($json_post_action));
    }

    public function testItThrowsAnExceptionIfProjectDoesNotUseExplicitBacklog(): void
    {
        $tracker  = Mockery::mock(Tracker::class)->shouldReceive('getGroupId')->andReturn(101)->getMock();
        $workflow = Mockery::mock(Workflow::class)->shouldReceive('getTracker')->andReturn($tracker)->getMock();

        $json_post_action = [
            'id' => 4,
            'type' => 'add_to_top_backlog'
        ];

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnFalse();

        $this->expectException(I18NRestException::class);

        $this->parser->parse($workflow, $json_post_action);
    }

    public function testItReturnsTheAddToTopBacklogValue(): void
    {
        $tracker  = Mockery::mock(Tracker::class)->shouldReceive('getGroupId')->andReturn(101)->getMock();
        $workflow = Mockery::mock(Workflow::class)->shouldReceive('getTracker')->andReturn($tracker)->getMock();

        $json_post_action = [
            'id' => 4,
            'type' => 'add_to_top_backlog'
        ];

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->andReturnTrue();

        $value_object = $this->parser->parse($workflow, $json_post_action);

        $this->assertInstanceOf(AddToTopBacklogValue::class, $value_object);
    }
}
