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
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_CriteriaTest extends TestCase
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

        $this->field = new class extends Tracker_FormElement_Field {

            public function __construct()
            {
                parent::__construct(
                    1,
                    1,
                    0,
                    'test_field',
                    'Test Field',
                    '',
                    true,
                    'P',
                    false,
                    null,
                    1
                );
            }

            public function accept(Tracker_FormElement_FieldVisitor $visitor)
            {
            }

            public static function getFactoryLabel()
            {
            }

            public static function getFactoryDescription()
            {
            }

            public static function getFactoryIconUseIt()
            {
            }

            public static function getFactoryIconCreate()
            {
            }

            protected function fetchAdminFormElement()
            {
            }

            public function getRESTAvailableValues()
            {
            }

            public function fetchCriteriaValue($criteria)
            {
            }

            public function fetchRawValue($value)
            {
            }

            public function getCriteriaFrom($criteria)
            {
            }

            public function getCriteriaWhere($criteria)
            {
            }

            protected function getCriteriaDao()
            {
            }

            protected function fetchArtifactValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value, array $submitted_values)
            {
            }

            public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
            {
            }

            protected function fetchSubmitValue(array $submitted_values)
            {
            }

            protected function fetchSubmitValueMasschange()
            {
            }

            protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
            {
            }

            protected function getValueDao()
            {
            }

            public function fetchFollowUp($artifact, $from, $to)
            {
            }

            public function fetchRawValueFromChangeset($changeset)
            {
            }

            protected function validate(Tracker_Artifact $artifact, $value)
            {
            }

            protected function saveValue($artifact, $changeset_value_id, $value, ?Tracker_Artifact_ChangesetValue $previous_changesetvalue, CreatedFileURLMapping $url_mapping)
            {
            }

            public function getChangesetValue($changeset, $value_id, $has_changed)
            {
            }

            public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report_id = null, $from_aid = null)
            {
            }
        };

        $this->criteria = Mockery::mock(Tracker_Report_Criteria::class);
    }

    public function testItSetsCriteriaValueFromXML(): void
    {
        $report_id = 'XML_IMPORT_' . rand();
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
