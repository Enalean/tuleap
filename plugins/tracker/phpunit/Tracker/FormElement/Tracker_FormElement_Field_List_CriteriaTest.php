<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_List_CriteriaTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List
     */
    private $field;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Report_Criteria
     */
    private $criteria;

    public function setUp(): void
    {
        parent::setUp();

        $this->field = Mockery::mock(Tracker_FormElement_Field_List::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->criteria = Mockery::mock(Tracker_Report_Criteria::class);
    }

    public function testItSetsCriteriaValueFromXML(): void
    {
        $report_id = 'XML_IMPORT_' . rand();
        $report    = Mockery::mock(Tracker_Report::class)->shouldReceive('getId')->andReturn($report_id)->getMock();
        $this->criteria->shouldReceive('getReport')->andReturn($report);

        $static_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->field->shouldReceive('getBind')->andReturn($static_bind);

        $xml_criteria_value = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <criteria_value type="list">
                <selected_value REF="V1"/>
            </criteria_value>
        ');

        $value_01 = Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value_02 = Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $mapping = [
            'V1' => $value_01,
            'V2' => $value_02
        ];

        $this->field->setCriteriaValueFromXML(
            $this->criteria,
            $xml_criteria_value,
            $mapping
        );

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this->field));

        $this->assertEquals(
            [$value_01],
            $cache->get($report_id)
        );
    }

    public function testItDoesNotSetCriteriaValueFromXMLIfNotAStaticBind(): void
    {
        $report_id = 'XML_IMPORT_' . rand();
        $report    = Mockery::mock(Tracker_Report::class)->shouldReceive('getId')->andReturn($report_id)->getMock();
        $this->criteria->shouldReceive('getReport')->andReturn($report);

        $user_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Users::class);
        $this->field->shouldReceive('getBind')->andReturn($user_bind);

        $xml_criteria_value = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <criteria_value type="list">
                <selected_value REF="V1"/>
            </criteria_value>
        ');

        $mapping = [];
        $this->field->setCriteriaValueFromXML(
            $this->criteria,
            $xml_criteria_value,
            $mapping
        );

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this->field));
        $this->assertFalse($cache->has($report_id));
    }
}
