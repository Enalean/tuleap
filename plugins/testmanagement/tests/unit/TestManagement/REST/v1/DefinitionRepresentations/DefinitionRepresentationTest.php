<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

use PFUser;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DefinitionRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsADescriptionWithCrossReferences(): void
    {
        $artifact   = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker_id = 10;
        $artifact->method('getTrackerId')->willReturn($tracker_id);
        $artifact->method('getId')->willReturn(1);
        $artifact->method('getLastChangeset')->willReturn(null);
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getGroupId')->willReturn(107);
        $artifact->method('getTracker')->willReturn($tracker);

        $user = UserTestBuilder::aUser()->build();

        $field = $this->createMock(\Tracker_FormElement_Field_Text::class);

        $value = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $value->method('getText')->willReturn('description');
        $artifact->expects(self::once())->method('getValue')->with($field, null)->willReturn($value);

        $form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $form_element_factory->method('getSelectboxFieldByNameForUser');
        $form_element_factory
            ->expects(self::exactly(4))
            ->method('getUsedFieldByNameForUser')
            ->willReturnCallback(
                static fn(int $called_tracker_id, string $called_field_name, PFUser $called_user) => match (true) {
                    $called_tracker_id === $tracker_id && $called_field_name === DefinitionRepresentation::FIELD_STEPS && $called_user === $user,
                    $called_tracker_id === $tracker_id && $called_field_name === DefinitionRepresentation::FIELD_SUMMARY && $called_user === $user,
                    $called_tracker_id === $tracker_id && $called_field_name === DefinitionRepresentation::FIELD_AUTOMATED_TESTS && $called_user === $user => null,
                    $called_tracker_id === $tracker_id && $called_field_name === DefinitionRepresentation::FIELD_DESCRIPTION && $called_user === $user => $field,
                }
            );

        $purifier = $this->createMock(\Codendi_HTMLPurifier::class);
        $purifier->expects(self::once())->method('purifyHTMLWithReferences')->willReturn('description');
        $commonmark_interpreter = $this->createMock(ContentInterpretor::class);
        $priority_manager       = $this->createStub(\Tracker_Artifact_PriorityManager::class);
        $priority_manager->method('getGlobalRank')->willReturn(1);

        $representation = new DefinitionTextOrHTMLRepresentation(
            $purifier,
            $commonmark_interpreter,
            $artifact,
            $this->createMock(ArtifactRepresentation::class),
            $form_element_factory,
            $priority_manager,
            $user,
            'text',
            [],
            null
        );

        self::assertEquals('description', $representation->description);
        self::assertEquals([], $representation->steps);
        self::assertEquals('', $representation->requirement);
        self::assertInstanceOf(ArtifactRepresentation::class, $representation->artifact);
    }
}
