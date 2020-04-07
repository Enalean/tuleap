<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;

class TrackerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private $tracker;
    private $formelement_factory;
    private $workflow_factory;

    public function setUp(): void
    {
        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);
        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);

        $this->formelement_factory = \Mockery::mock(\Tracker_FormElementFactory::class);

        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->shouldReceive('getFormElementFactory')->andReturns($this->formelement_factory);
        $this->tracker->shouldReceive('getId')->andReturns(110);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testHasBlockingErrorWorkflowThrowException()
    {
        $header = array('summary', 'details');
        $lines = array(
            array('summary 1', 'details 1'),
            array('summary 2', 'details 2'),
        );
        $field1 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field2 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $this->formelement_factory->shouldReceive("getUsedFields")->andReturns(array($field1, $field2));

        $field1->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);
        $field2->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);

        $field1->shouldReceive('getId')->andReturns(1);
        $field2->shouldReceive('getId')->andReturns(2);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $tracker_artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->tracker->shouldReceive('getTrackerArtifactFactory')->andReturns($tracker_artifact_factory);
        $this->tracker->shouldReceive('aidExists')->with('0')->andReturns(false);

        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 1', $artifact)->andReturns('summary 1')->once();
        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 2', $artifact)->andReturns('summary 2')->once();

        $field2->shouldReceive('getFieldDataFromCSVValue')->with('details 1', $artifact)->andReturns('details 1')->once();
        $field2->shouldReceive('getFieldDataFromCSVValue')->with('details 2', $artifact)->andReturns('details 2')->once();

        $field1->shouldReceive('isCSVImportable')->andReturns(true);
        $field2->shouldReceive('isCSVImportable')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'summary')->andReturns($field1);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'details')->andReturns($field2);
        $this->tracker->shouldReceive('getWorkflow')->andReturns($this->workflow_factory);

        $user_manager = \Mockery::mock(\UserManager::class);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('107');
        $this->tracker->shouldReceive('getUserManager')->andReturns($user_manager);
        $user_manager->shouldReceive('getCurrentUser')->andReturns($user);

        $tracker_artifact_factory->shouldReceive('getInstanceFromRow')->andReturns($artifact);

        $this->workflow_factory->shouldReceive('getGlobalRulesManager')->andThrows(\Mockery::spy(\Tracker_Workflow_GlobalRulesViolationException::class));

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', Mockery::any(), Mockery::any());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }

    public function testHasBlockingErrorNoError()
    {
        $header = array('summary', 'details');
        $lines = array(
            array('summary 1', 'details 1'),
            array('summary 2', 'details 2'),
        );
        $field1 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field2 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $this->formelement_factory->shouldReceive("getUsedFields")->andReturns(array($field1, $field2));

        $field1->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);
        $field2->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);

        $field1->shouldReceive('getId')->andReturns(1);
        $field2->shouldReceive('getId')->andReturns(2);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $tracker_artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->tracker->shouldReceive('getTrackerArtifactFactory')->andReturns($tracker_artifact_factory);
        $this->tracker->shouldReceive('aidExists')->with('0')->andReturns(false);

        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 1', $artifact)->andReturns('summary 1')->once();
        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 2', $artifact)->andReturns('summary 2')->once();

        $field2->shouldReceive('getFieldDataFromCSVValue')->with('details 1', $artifact)->andReturns('details 1')->once();
        $field2->shouldReceive('getFieldDataFromCSVValue')->with('details 2', $artifact)->andReturns('details 2')->once();

        $field1->shouldReceive('isCSVImportable')->andReturns(true);
        $field2->shouldReceive('isCSVImportable')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'summary')->andReturns($field1);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'details')->andReturns($field2);
        $this->tracker->shouldReceive('getWorkflow')->andReturns($this->workflow_factory);

        $user_manager = \Mockery::mock(\UserManager::class);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('107');
        $this->tracker->shouldReceive('getUserManager')->andReturns($user_manager);
        $user_manager->shouldReceive('getCurrentUser')->andReturns($user);

        $tracker_artifact_factory->shouldReceive('getInstanceFromRow')->andReturns($artifact);

        $this->workflow_factory->shouldReceive('getGlobalRulesManager')->andReturns(\Mockery::spy(\Tracker_RulesManager::class));

        $GLOBALS['Response']->shouldNotReceive('addFeedback')->with('error', Mockery::any(), Mockery::any());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }

    public function testHasBlockingErrorReturnNoErrorWhenEmptyValue(): void
    {
        $header = ['summary', 'details'];
        $lines = [
            ['summary 1', 'details 1'],
            ['summary 2', ''],
        ];
        $field1 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field2 = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $this->formelement_factory->shouldReceive("getUsedFields")->andReturns(array($field1, $field2));

        $field1->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);
        $field2->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturns(true);

        $field1->shouldReceive('getId')->andReturns(1);
        $field2->shouldReceive('getId')->andReturns(2);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $tracker_artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->tracker->shouldReceive('getTrackerArtifactFactory')->andReturns($tracker_artifact_factory);
        $this->tracker->shouldReceive('aidExists')->with('0')->andReturns(false);

        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 1', $artifact)->andReturns('summary 1')->once();
        $field1->shouldReceive('getFieldDataFromCSVValue')->with('summary 2', $artifact)->andReturns('summary 2')->once();

        $field2->shouldReceive('getFieldDataFromCSVValue')->with('details 1', $artifact)->andReturns('details 1')->once();
        $field2->shouldReceive('getFieldDataFromCSVValue')->with('', $artifact)->andReturns(100)->once();

        $field1->shouldReceive('isCSVImportable')->andReturns(true);
        $field2->shouldReceive('isCSVImportable')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'summary')->andReturns($field1);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with(110, 'details')->andReturns($field2);
        $this->tracker->shouldReceive('getWorkflow')->andReturns($this->workflow_factory);

        $user_manager = \Mockery::mock(\UserManager::class);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('107');
        $this->tracker->shouldReceive('getUserManager')->andReturns($user_manager);
        $user_manager->shouldReceive('getCurrentUser')->andReturns($user);

        $tracker_artifact_factory->shouldReceive('getInstanceFromRow')->andReturns($artifact);

        $this->workflow_factory->shouldReceive('getGlobalRulesManager')->andReturns(\Mockery::spy(\Tracker_RulesManager::class));

        $GLOBALS['Response']->shouldNotReceive('addFeedback')->with('error', Mockery::any(), Mockery::any());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }
}
