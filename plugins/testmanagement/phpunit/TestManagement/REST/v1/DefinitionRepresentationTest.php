<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetValue;

class DefinitionRepresentationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->form_element_factory->shouldReceive('getSelectboxFieldByNameForUser');
    }

    public function testItBuildsADescriptionWithCrossReferences(): void
    {
        $artifact   = \Mockery::mock(\Tracker_Artifact::class);
        $tracker_id = 10;
        $artifact->shouldReceive('getTrackerId')->andReturn($tracker_id);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('getLastChangeset')->andReturn(null);
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(107);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $user = \Mockery::mock(\PFUser::class);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);

        $value = \Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $value->shouldReceive('getText')->andReturn("description");
        $artifact->shouldReceive('getValue')->once()->withArgs([$field, null])->andReturn($value);


        $this->useFieldByName($tracker_id, $user, DefinitionRepresentation::FIELD_STEPS, null);
        $this->useFieldByName($tracker_id, $user, DefinitionRepresentation::FIELD_SUMMARY, null);
        $this->useFieldByName($tracker_id, $user, DefinitionRepresentation::FIELD_AUTOMATED_TESTS, null);
        $this->useFieldByName($tracker_id, $user, DefinitionRepresentation::FIELD_DESCRIPTION, $field);

        $purifier = \Mockery::mock(\Codendi_HTMLPurifier::class);
        $purifier->shouldReceive('purifyHTMLWithReferences')->andReturn("description")->once();

        $representation = new DefinitionRepresentation($purifier);
        $representation->build($artifact, $this->form_element_factory, $user);

        $this->assertEquals("description", $representation->description);
        $this->assertEquals([], $representation->steps);
        $this->assertEquals("", $representation->requirement);
    }

    private function useFieldByName(int $tracker_id, $user, string $name, ?\Tracker_FormElement_Field $value): void
    {
        $this->form_element_factory->shouldReceive('getUsedFieldByNameForUser')->withArgs(
            [
                $tracker_id,
                $name,
                $user
            ]
        )->once()->andReturn($value);
    }
}
