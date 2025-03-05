<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
use SimpleXMLElement;
use Tracker_FormElement_Field;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Tracker_FormElement_Field_CriteriaTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field
     */
    private $field;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Report_Criteria
     */
    private $criteria;

    public function setUp(): void
    {
        parent::setUp();

        $this->field = TextFieldBuilder::aTextField(1)->build();

        $this->criteria = Mockery::mock(Tracker_Report_Criteria::class);
    }

    public function testItSetsCriteriaValueFromXML(): void
    {
        $report_id = 'XML_IMPORT_' . bin2hex(random_bytes(32));
        $report    = Mockery::mock(Tracker_Report::class)->shouldReceive('getId')->andReturn($report_id)->getMock();
        $this->criteria->shouldReceive('getReport')->andReturn($report);

        $xml_criteria_value = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <criteria_value type="text"><![CDATA[My text]]></criteria_value>
        ');

        $mapping = [];
        $this->field->setCriteriaValueFromXML(
            $this->criteria,
            $xml_criteria_value,
            $mapping
        );

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this->field));

        $this->assertEquals(
            'My text',
            $cache->get($report_id)
        );
    }
}
