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

use Tuleap\Tracker\FormElement\TransitionListValidator;

require_once __DIR__ . '/../../bootstrap.php';

class TransitionListValidatorTest extends \PHPUnit\Framework\TestCase  // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var TransitionListValidator
     */
    private $transition_validator;

    /**
     * @var TransitionFactory|\Mockery\MockInterface
     */
    private $transition_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->transition_factory   = Mockery::mock(TransitionFactory::class);
        $this->transition_validator = new TransitionListValidator($this->transition_factory);
    }

    public function testTransitionToParamIsCorrectlyExtractedForStringFields()
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field     = Mockery::mock(Tracker_FormElement_Field_List::class);
        $value     = 'Closed';
        $tracker   = Mockery::mock(Tracker::class);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $changeset_value->shouldReceive('getListValues')->andReturn(['Open', 'Waiting for Information', 'Closed']);

        $this->transition_factory->shouldReceive('getTransitionId')->withArgs([$tracker, 'Open', $value])->andReturn(10);

        $field->shouldReceive('userCanMakeTransition')->withArgs([10])->andReturn(true);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $this->assertTrue($this->transition_validator->checkTransition($field, $value, $changeset));
    }

    public function testTransitionToParamIsCorrectlyExtractedForListFields()
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field     = Mockery::mock(Tracker_FormElement_Field_List::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $value->shouldReceive('getId')->andReturn('101');
        $tracker = Mockery::mock(Tracker::class);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $changeset_value->shouldReceive('getListValues')->andReturn(['Open', 'Wainting for Information', 'Closed']);

        $this->transition_factory->shouldReceive('getTransitionId')->withArgs([$tracker, 'Open', '101'])->andReturn(10);

        $field->shouldReceive('userCanMakeTransition')->withArgs([10])->andReturn(true);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $this->assertTrue($this->transition_validator->checkTransition($field, $value, $changeset));
    }

    public function testTransitionIsInvalidWhenUserDoesNotHaveSufficientPermissions()
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field     = Mockery::mock(Tracker_FormElement_Field_List::class);
        $value     = 'Closed';
        $tracker   = Mockery::mock(Tracker::class);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $changeset_value->shouldReceive('getListValues')->andReturn(['Open', 'Waiting for Information', 'Closed']);

        $this->transition_factory->shouldReceive('getTransitionId')->withArgs([$tracker, 'Open', $value])->andReturn(10);

        $field->shouldReceive('userCanMakeTransition')->withArgs([10])->andReturn(false);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $this->assertFalse($this->transition_validator->checkTransition($field, $value, $changeset));
    }
}
