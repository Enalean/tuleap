<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_List_Value;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;

class SemanticDoneLoaderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|SemanticDoneValueChecker
     */
    private $value_checker;
    /**
     * @var \Mockery\MockInterface|SemanticDoneDao
     */
    private $dao;
    /**
     * @var SemanticDoneLoader
     */
    private $loader;
    /**
     * @var Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\MockInterface|Tracker_Semantic_Status
     */
    private $semantic_status;

    public function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(101);

        $this->semantic_status = Mockery::mock(Tracker_Semantic_Status::class);

        $this->dao           = Mockery::mock(SemanticDoneDao::class);
        $this->value_checker = Mockery::mock(SemanticDoneValueChecker::class);

        $this->loader = new SemanticDoneLoader($this->dao, $this->value_checker);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testLoadWhenStatusIsNotDefined(): void
    {
        $this->semantic_status->shouldReceive('getField')->andReturn(null);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([], $semantic_done->getDoneValuesIds());
    }

    public function testLoadWhenStatusIsDefined(): void
    {
        $done_value = Mockery::mock(Tracker_FormElement_Field_List_Value::class);
        $done_value->shouldReceive('getId')->andReturn(3);

        $delivered_value = Mockery::mock(Tracker_FormElement_Field_List_Value::class);
        $delivered_value->shouldReceive('getId')->andReturn(4);

        $this->value_checker->shouldReceive('isValueAPossibleDoneValue')->andReturn(true);

        $bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive('getValue')->with(3)->andReturn($done_value);
        $bind->shouldReceive('getValue')->with(4)->andReturn($delivered_value);

        $status_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getBind')->andReturn($bind);

        $this->semantic_status->shouldReceive('getField')->andReturn($status_field);
        $this->dao->shouldReceive('getSelectedValues')->andReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3, 4], $semantic_done->getDoneValuesIds());
    }

    public function testLoadIgnoreValuesThatCannotBeDone(): void
    {
        $done_value = Mockery::mock(Tracker_FormElement_Field_List_Value::class);
        $done_value->shouldReceive('getId')->andReturn(3);

        $delivered_value = Mockery::mock(Tracker_FormElement_Field_List_Value::class);
        $delivered_value->shouldReceive('getId')->andReturn(4);

        $this->value_checker
            ->shouldReceive('isValueAPossibleDoneValue')
            ->with($done_value, $this->semantic_status)
            ->andReturn(true);
        $this->value_checker
            ->shouldReceive('isValueAPossibleDoneValue')
            ->with($delivered_value, $this->semantic_status)
            ->andReturn(false);

        $bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive('getValue')->with(3)->andReturn($done_value);
        $bind->shouldReceive('getValue')->with(4)->andReturn($delivered_value);

        $status_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getBind')->andReturn($bind);

        $this->semantic_status->shouldReceive('getField')->andReturn($status_field);
        $this->dao->shouldReceive('getSelectedValues')->andReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3], $semantic_done->getDoneValuesIds());
    }

    public function testLoadIgnoreUnknownValues(): void
    {
        $done_value = Mockery::mock(Tracker_FormElement_Field_List_Value::class);
        $done_value->shouldReceive('getId')->andReturn(3);

        $this->value_checker->shouldReceive('isValueAPossibleDoneValue')->andReturn(true);

        $bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive('getValue')->with(3)->andReturn($done_value);
        $bind->shouldReceive('getValue')->with(4)->andThrow(Tracker_FormElement_InvalidFieldValueException::class);

        $status_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getBind')->andReturn($bind);

        $this->semantic_status->shouldReceive('getField')->andReturn($status_field);
        $this->dao->shouldReceive('getSelectedValues')->andReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3], $semantic_done->getDoneValuesIds());
    }
}
