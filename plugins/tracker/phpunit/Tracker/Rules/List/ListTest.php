<?php
/**
  * Copyright (c) Enalean, 2012 -present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

declare(strict_types=1);

namespace Tuleap\Tracker\Rule;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Rule_List;

class Tracker_Rule_List_ListTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Rule_List
     */
    private $list_rule;

    public function setUp(): void
    {
        $this->list_rule = new Tracker_Rule_List();
    }

    /*
     * Source Field tests
     */
    public function testSetSourceFieldIdReturnsModelObject(): void
    {
        $set = $this->list_rule->setSourceFieldId(123);
        $this->assertEquals($this->list_rule, $set);
    }

    public function testGetSourceFieldIdReturnsFieldIdSet(): void
    {
        $this->list_rule->setSourceFieldId(45);
        $this->assertEquals(45, $this->list_rule->getSourceFieldId());
    }

    /*
     * Target Field tests
     */
    public function testSetTargetFieldIdReturnsModelObject(): void
    {
        $set = $this->list_rule->setSourceFieldId(123);
        $this->assertEquals($this->list_rule, $set);
    }

    public function testGetTargetFieldIdReturnsTargetIdSet(): void
    {
        $this->list_rule->setTargetFieldId(45);
        $this->assertEquals(45, $this->list_rule->getTargetFieldId());
    }

    /*
     * Tracker Field tests
     */
    public function testSetTrackerFieldIdReturnsModelObject(): void
    {
        $set = $this->list_rule->setTrackerId(123);
        $this->assertEquals($this->list_rule, $set);
    }

    public function testGetTrackerFieldIdReturnsTrackerIdSet(): void
    {
        $this->list_rule->setTrackerId(45);
        $this->assertEquals(45, $this->list_rule->getTrackerId());
    }

    /*
     * Source Field value tests
     */
    public function testSetSourceValueReturnsModelObject(): void
    {
        $set = $this->list_rule->setSourceValue(123);
        $this->assertEquals($this->list_rule, $set);
    }

    public function testGetSourceValueReturnsFieldIdSet(): void
    {
        $this->list_rule->setSourceValue(45);
        $this->assertEquals(45, $this->list_rule->getSourceValue());
    }

    /*
     * Target Field value tests
     */
    public function testSetTargetValueReturnsModelObject(): void
    {
        $set = $this->list_rule->setSourceValue(123);
        $this->assertEquals($this->list_rule, $set);
    }

    public function testGetTargetValueReturnsTargetIdSet(): void
    {
        $this->list_rule->setTargetValue(45);
        $this->assertEquals(45, $this->list_rule->getTargetValue());
    }
}
