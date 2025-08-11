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

namespace Tuleap\Tracker\Semantic\Progress;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MethodBasedOnEffortTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private Artifact $artifact;
    private SemanticProgressDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao      = $this->createMock(SemanticProgressDao::class);
        $this->user     = UserTestBuilder::buildWithDefaults();
        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
    }

    #[\PHPUnit\Framework\Attributes\TestWith([8, 5.25, 0.34375, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 8, 0, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 0, 1, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([0, 0, null, 'There is no total effort.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, -2, null, 'Remaining effort cannot be negative.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([-2, 3, null, 'Total effort cannot be negative.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 10, null, 'Remaining effort cannot be greater than total effort.'])]
    public function testItComputesTheProgressWithFloatAndIntFields(
        int $total_effort,
        float $remaining_effort,
        ?float $expected_progress_value,
        string $expected_error_message,
    ): void {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, true)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, true)
            ->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $total_effort_last_changeset     = $this->createMock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $remaining_effort_last_changeset = $this->createMock(\Tracker_Artifact_ChangesetValue_Float::class);

        $total_effort_last_changeset->method('getNumeric')->willReturn($total_effort);
        $remaining_effort_last_changeset->method('getNumeric')->willReturn($remaining_effort);

        $changeset = ChangesetTestBuilder::aChangeset(101)->build();
        $changeset->setFieldValue($total_effort_field, $total_effort_last_changeset);
        $changeset->setFieldValue($remaining_effort_field, $remaining_effort_last_changeset);

        $artifact = $changeset->getArtifact();
        $artifact->setChangesets([$changeset]);

        $progression_result = $method->computeProgression($artifact, $this->user);

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
        $this->assertEquals($expected_error_message, $progression_result->getErrorMessage());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([8, 5.25, 0.34375, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 8, 0, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 0, 1, ''])]
    #[\PHPUnit\Framework\Attributes\TestWith([0, 0, null, 'There is no total effort.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, -2, null, 'Remaining effort cannot be negative.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([-2, 3, null, 'Total effort cannot be negative.'])]
    #[\PHPUnit\Framework\Attributes\TestWith([8, 10, null, 'Remaining effort cannot be greater than total effort.'])]
    public function testItComputesProgressWithComputedFields(
        ?float $total_effort,
        ?float $remaining_effort,
        ?float $expected_progress_value,
        string $expected_error_message,
    ): void {
        $computed_field_total_effort     = $this->createMock(\Tuleap\Tracker\FormElement\Field\Computed\ComputedField::class);
        $computed_field_remaining_effort = $this->createMock(\Tuleap\Tracker\FormElement\Field\Computed\ComputedField::class);

        $computed_field_total_effort
            ->expects($this->once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);
        $computed_field_remaining_effort
            ->expects($this->once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $computed_field_total_effort->method('getComputedValue')->with($this->user, $this->artifact)->willReturn($total_effort);
        $computed_field_remaining_effort->method('getComputedValue')->with($this->user, $this->artifact)->willReturn($remaining_effort);

        $method = new MethodBasedOnEffort(
            $this->dao,
            $computed_field_total_effort,
            $computed_field_remaining_effort
        );

        $progression_result = $method->computeProgression($this->artifact, $this->user);

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
        $this->assertEquals($expected_error_message, $progression_result->getErrorMessage());
    }

    public function testItReturnsNullWhenUserHasNotPermissionToReadTotalEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, false)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, true)
            ->build();

        $method             = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );
        $progression_result = $method->computeProgression($this->artifact, $this->user);

        $this->assertEquals(null, $progression_result->getValue());
        $this->assertEquals('', $progression_result->getErrorMessage());
    }

    public function testItReturnsNullWhenUserHasNotPermissionToReadRemainingEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, true)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, false)
            ->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $progression_result = $method->computeProgression($this->artifact, $this->user);

        $this->assertEquals(null, $progression_result->getValue());
        $this->assertEquals('', $progression_result->getErrorMessage());
    }

    public function testItExportsToREST(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, true)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, true)
            ->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        self::assertEquals(
            new SemanticProgressBasedOnEffortRepresentation(1001, 1002),
            $method->exportToREST($this->user),
        );
    }

    public function testItExportsNothingToRESTIfUserCannotReadTotalEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, false)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, true)
            ->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        self::assertNull($method->exportToREST($this->user));
    }

    public function testItExportsNothingToRESTIfUserCannotReadRemainingEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)
            ->withReadPermission($this->user, true)
            ->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)
            ->withReadPermission($this->user, false)
            ->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        self::assertNull($method->exportToREST($this->user));
    }

    public function testItExportsSemanticConfigurationToXml(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $root     = new \SimpleXMLElement($xml_data);

        $method->exportToXMl($root, [
            'F201' => 1001,
            'F202' => 1002,
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('progress', (string) $root->semantic['type']);
        $this->assertEquals('F201', (string) $root->semantic->total_effort_field['REF']);
        $this->assertEquals('F202', (string) $root->semantic->remaining_effort_field['REF']);
    }

    public function testItDoesNotExportToXMLWhenThereIsNoReferenceToTotalEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $root     = new \SimpleXMLElement($xml_data);

        $method->exportToXMl($root, [
            'F202' => 1002,
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLWhenThereIsNoReferenceToRemainingEffortField(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $root     = new \SimpleXMLElement($xml_data);

        $method->exportToXMl($root, [
            'F201' => 1001,
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItSavesItsConfiguration(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $this->dao->expects($this->once())->method('save')->with(113, 1001, 1002, null)->willReturn(true);

        $method->saveSemanticForTracker($tracker);
    }

    public function testItDeletesItsConfiguration(): void
    {
        $total_effort_field     = IntegerFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntegerFieldBuilder::anIntField(1002)->build();
        $method                 = new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $this->dao->expects($this->once())->method('delete')->with(113)->willReturn(true);

        $this->assertTrue(
            $method->deleteSemanticForTracker($tracker)
        );
    }
}
