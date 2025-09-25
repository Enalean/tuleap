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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_RulesManager;
use Tracker_Workflow_GlobalRulesViolationException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use UserManager;
use Workflow;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerBlockingErrorTest extends TestCase
{
    use GlobalResponseMock;

    private Tracker&MockObject $tracker;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Workflow&MockObject $workflow;

    #[\Override]
    public function setUp(): void
    {
        $this->workflow = $this->createMock(Workflow::class);

        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->tracker = $this->createPartialMock(Tracker::class, [
            'getFormElementFactory', 'getId', 'getTrackerArtifactFactory', 'aidExists', 'getWorkflow', 'getUserManager',
        ]);
        $this->tracker->method('getFormElementFactory')->willReturn($this->formelement_factory);
        $this->tracker->method('getId')->willReturn(110);
    }

    public function testGetSubmitUrlWithParameters(): void
    {
        self::assertEquals(
            '/plugins/tracker/?tracker=110&func=new-artifact&whatever=foo',
            $this->tracker->getSubmitUrlWithParameters([
                'whatever' => 'foo',
            ])
        );
    }

    public function testHasBlockingErrorWorkflowThrowException()
    {
        $header = ['summary', 'details'];
        $lines  = [
            ['summary 1', 'details 1'],
            ['summary 2', 'details 2'],
        ];
        $field1 = $this->createMock(StringField::class);
        $field2 = $this->createMock(StringField::class);
        $this->formelement_factory->method('getUsedFields')->willReturn([$field1, $field2]);
        $field1->method('isRequired')->willReturn(false);
        $field2->method('isRequired')->willReturn(false);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $field1->method('getId')->willReturn(1);
        $field2->method('getId')->willReturn(2);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(101);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->method('getLastChangeset')->willReturn(null);
        $artifact->method('getWorkflow')->willReturn(null);
        $tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->tracker->method('getTrackerArtifactFactory')->willReturn($tracker_artifact_factory);
        $this->tracker->method('aidExists')->with('0')->willReturn(false);

        $field1->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'summary 1' || $value === 'summary 2'),
            $artifact,
        )->willReturnArgument(0);

        $field2->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'details 1' || $value === 'details 2'),
            $artifact,
        )->willReturnArgument(0);

        $field1->method('isCSVImportable')->willReturn(true);
        $field2->method('isCSVImportable')->willReturn(true);

        $this->formelement_factory->method('getUsedFieldByName')->with(110, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'summary' => $field1,
                'details' => $field2,
            });
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);

        $user_manager = $this->createMock(UserManager::class);
        $user         = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn('107');
        $this->tracker->method('getUserManager')->willReturn($user_manager);
        $user_manager->method('getCurrentUser')->willReturn($user);

        $tracker_artifact_factory->method('getInstanceFromRow')->willReturn($artifact);

        $this->workflow->method('checkGlobalRules')->willThrowException($this->createMock(Tracker_Workflow_GlobalRulesViolationException::class));

        $GLOBALS['Response']->method('addFeedback')->with('error', self::anything(), self::anything());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }

    public function testHasBlockingErrorNoError(): void
    {
        $header = ['summary', 'details'];
        $lines  = [
            ['summary 1', 'details 1'],
            ['summary 2', 'details 2'],
        ];
        $field1 = $this->createMock(StringField::class);
        $field2 = $this->createMock(StringField::class);
        $this->formelement_factory->method('getUsedFields')->willReturn([$field1, $field2]);
        $field1->method('isRequired')->willReturn(false);
        $field2->method('isRequired')->willReturn(false);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $field1->method('getId')->willReturn(1);
        $field2->method('getId')->willReturn(2);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(101);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->method('getLastChangeset')->willReturn(null);
        $artifact->method('getWorkflow')->willReturn(null);
        $tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->tracker->method('getTrackerArtifactFactory')->willReturn($tracker_artifact_factory);
        $this->tracker->method('aidExists')->with('0')->willReturn(false);

        $field1->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'summary 1' || $value === 'summary 2'),
            $artifact,
        )->willReturnArgument(0);

        $field2->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'details 1' || $value === 'details 2'),
            $artifact,
        )->willReturnArgument(0);

        $field1->method('isCSVImportable')->willReturn(true);
        $field2->method('isCSVImportable')->willReturn(true);

        $this->formelement_factory->method('getUsedFieldByName')->with(110, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'summary' => $field1,
                'details' => $field2,
            });
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);

        $user_manager = $this->createMock(UserManager::class);
        $user         = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn('107');
        $this->tracker->method('getUserManager')->willReturn($user_manager);
        $user_manager->method('getCurrentUser')->willReturn($user);

        $tracker_artifact_factory->method('getInstanceFromRow')->willReturn($artifact);

        $this->workflow->method('checkGlobalRules')->willReturn(true);
        $this->workflow->method('getGlobalRulesManager')->willReturn($this->createMock(Tracker_RulesManager::class));

        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with('error', self::anything(), self::anything());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }

    public function testHasBlockingErrorReturnNoErrorWhenEmptyValue(): void
    {
        $header = ['summary', 'details'];
        $lines  = [
            ['summary 1', 'details 1'],
            ['summary 2', ''],
        ];
        $field1 = $this->createMock(StringField::class);
        $field2 = $this->createMock(StringField::class);
        $this->formelement_factory->method('getUsedFields')->willReturn([$field1, $field2]);
        $field1->method('isRequired')->willReturn(false);
        $field2->method('isRequired')->willReturn(false);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $field1->method('getId')->willReturn(1);
        $field2->method('getId')->willReturn(2);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(101);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->method('getLastChangeset')->willReturn(null);
        $artifact->method('getWorkflow')->willReturn(null);
        $tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->tracker->method('getTrackerArtifactFactory')->willReturn($tracker_artifact_factory);
        $this->tracker->method('aidExists')->with('0')->willReturn(false);

        $field1->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'summary 1' || $value === 'summary 2'),
            $artifact,
        )->willReturnArgument(0);

        $field2->expects($this->exactly(2))->method('getFieldDataFromCSVValue')->with(
            self::callback(static fn(string $value) => $value === 'details 1' || $value === ''),
            $artifact,
        )->willReturnCallback(static fn(string $value) => $value === '' ? 100 : $value);

        $field1->method('isCSVImportable')->willReturn(true);
        $field2->method('isCSVImportable')->willReturn(true);

        $this->formelement_factory->method('getUsedFieldByName')->with(110, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'summary' => $field1,
                'details' => $field2,
            });
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);

        $user_manager = $this->createMock(UserManager::class);
        $user         = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn('107');
        $this->tracker->method('getUserManager')->willReturn($user_manager);
        $user_manager->method('getCurrentUser')->willReturn($user);

        $tracker_artifact_factory->method('getInstanceFromRow')->willReturn($artifact);

        $this->workflow->method('checkGlobalRules')->willReturn(true);
        $this->workflow->method('getGlobalRulesManager')->willReturn($this->createMock(Tracker_RulesManager::class));

        $GLOBALS['Response']->expects($this->never())->method('addFeedback')->with('error', self::anything(), self::anything());
        $this->assertFalse($this->tracker->hasBlockingError($header, $lines));
    }
}
