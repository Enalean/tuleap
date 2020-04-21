<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class MoveTitleSemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveTitleSemanticChecker
     */
    private $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = new MoveTitleSemanticChecker();

        $this->source_tracker = Mockery::mock(\Tracker::class);
        $this->target_tracker = Mockery::mock(\Tracker::class);
    }

    public function testItReturnsTrueIfBothSemanticsAreDefined()
    {
        $this->source_tracker->shouldReceive('hasSemanticsTitle')->once()->andReturn(true);
        $this->target_tracker->shouldReceive('hasSemanticsTitle')->once()->andReturn(true);

        $this->assertTrue($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfAtLeastOneSemanticsIsNotDefined()
    {
        $this->source_tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);
        $this->target_tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->target_tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);
        $this->target_tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testFieldsHaveAlwaysTheSameType()
    {
        $this->assertTrue($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }
}
