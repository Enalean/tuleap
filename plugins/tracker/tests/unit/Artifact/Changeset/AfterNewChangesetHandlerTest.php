<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AfterNewChangesetHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SaveArtifactStub $artifact_saver;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject & \Workflow
     */
    private $workflow;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub & \Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->artifact_saver       = SaveArtifactStub::withSuccess();
        $this->workflow             = $this->createMock(\Workflow::class);
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
    }

    private function handle(): bool
    {
        $artifact = ArtifactTestBuilder::anArtifact(56)
            ->inTracker(TrackerTestBuilder::aTracker()->build())
            ->build();

        $handler = new AfterNewChangesetHandler(
            $this->artifact_saver,
            new FieldsToBeSavedInSpecificOrderRetriever($this->form_element_factory)
        );
        return $handler->handle(
            $artifact,
            [],
            UserTestBuilder::buildWithDefaults(),
            $this->workflow,
            ChangesetTestBuilder::aChangeset(3311)->ofArtifact($artifact)->build(),
            ChangesetTestBuilder::aChangeset(3310)->ofArtifact($artifact)->build()
        );
    }

    public function testItTriggersAllFieldsPostSaveNewChangesetAndWorkflowAfter(): void
    {
        $string_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $file_field   = $this->createMock(\Tracker_FormElement_Field_File::class);
        $this->form_element_factory->method('getUsedFields')->willReturn([$string_field, $file_field]);
        $this->form_element_factory->method('isFieldAFileField')->willReturnOnConsecutiveCalls(false, true);

        $file_field->expects(self::once())->method('postSaveNewChangeset');
        $string_field->expects(self::once())->method('postSaveNewChangeset');
        $this->workflow->expects(self::once())->method('after');

        self::assertTrue($this->handle());
    }

    public function testItReturnsFalseWhenItCannotSaveArtifact(): void
    {
        $this->artifact_saver = SaveArtifactStub::withFailure();
        self::assertFalse($this->handle());
    }
}
