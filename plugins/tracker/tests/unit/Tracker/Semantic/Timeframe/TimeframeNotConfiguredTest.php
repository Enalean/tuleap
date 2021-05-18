<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace unit\Tracker\Semantic\Timeframe;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

class TimeframeNotConfiguredTest extends TestCase
{
    /**
     * @var TimeframeNotConfigured
     */
    private $timeframe;

    protected function setUp(): void
    {
        $this->timeframe = new TimeframeNotConfigured();
    }

    public function testItReturnsItsConfigDescription(): void
    {
        self::assertEquals(
            'This semantic is not defined yet.',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItDoesNotExportToXML(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002
        ]);
        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToREST(): void
    {
        $user = $this->getMockBuilder(\PFUser::class)->disableOriginalConstructor()->getMock();
        self::assertNull(
            $this->timeframe->exportToREST($user)
        );
    }

    public function testItFieldsAreAlwaysUnused(): void
    {
        $a_field = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertFalse($this->timeframe->isFieldUsed($a_field));
    }

    public function testItIsNotDefined(): void
    {
        self::assertFalse($this->timeframe->isDefined());
    }

    public function testItDoesNotSave(): void
    {
        $dao     = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $tracker = $this->getMockBuilder(\Tracker::class)->disableOriginalConstructor()->getMock();

        $dao->expects(self::never())->method('save');

        self::assertFalse(
            $this->timeframe->save($tracker, $dao)
        );
    }
}
