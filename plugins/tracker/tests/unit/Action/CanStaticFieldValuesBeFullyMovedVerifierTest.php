<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tracker_Artifact_ChangesetValue_List;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingBindValueByDuckTypingStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CanStaticFieldValuesBeFullyMovedVerifierTest extends TestCase
{
    private Artifact $artifact;
    private Stub&ListField $destination_list_field;
    private MockObject&ListField $source_list_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->source_list_field = $this->createMock(ListField::class);
        $this->source_list_field->method('getId')->willReturn('123');
        $this->source_list_field->method('getName')->willReturn('List');
        $this->destination_list_field = $this->createStub(ListField::class);
        $this->destination_list_field->method('getId')->willReturn('456');
        $this->destination_list_field->method('getName')->willReturn('List');
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
    }

    public function testFieldIsPartiallyMovedWhenValueDoesNotExistsInDestinationTracker(): void
    {
        $last_changeset_value_value = ListStaticValueBuilder::aStaticValue('A value')->build();
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_list_field->expects($this->once())->method('getLastChangesetValue')->with($this->artifact)->willReturn($last_changeset_value);
        $verifier = new CanStaticFieldValuesBeFullyMovedVerifier(RetrieveMatchingBindValueByDuckTypingStub::withoutMatchingBindValue());
        $this->assertFalse($verifier->canAllStaticFieldValuesBeMoved($this->source_list_field, $this->destination_list_field, $this->artifact, new NullLogger()));
    }

    public function testFieldCanBeFullyMovedWhenValueIsFoundInDestinationTracker(): void
    {
        $last_changeset_value_value = ListUserValueBuilder::aUserWithId(138)->build();
        $last_changeset_value       = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value->method('getListValues')->willReturn([$last_changeset_value_value]);

        $this->source_list_field->expects($this->once())->method('getLastChangesetValue')->with($this->artifact)->willReturn($last_changeset_value);
        $bind     = ListStaticValueBuilder::aStaticValue('my value')->build();
        $verifier = new CanStaticFieldValuesBeFullyMovedVerifier(RetrieveMatchingBindValueByDuckTypingStub::withMatchingBindValue($bind));

        $this->assertTrue($verifier->canAllStaticFieldValuesBeMoved($this->source_list_field, $this->destination_list_field, $this->artifact, new NullLogger()));
    }
}
