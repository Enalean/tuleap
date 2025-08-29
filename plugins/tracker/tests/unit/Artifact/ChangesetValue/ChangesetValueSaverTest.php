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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID         = 533;
    private const NEW_CHANGESET_ID = 866;
    private const FIELD_VALUE      = 'Sufistic pathogenous';
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject & \Tuleap\Tracker\FormElement\Field\TrackerField
     */
    private $field;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub & \Workflow
     */
    private $workflow;
    private array $fields_data;

    protected function setUp(): void
    {
        $this->field = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $this->field->method('getId')->willReturn(self::FIELD_ID);
        $this->workflow    = $this->createStub(\Workflow::class);
        $this->fields_data = [];
    }

    private function save(): bool
    {
        $artifact           = ArtifactTestBuilder::anArtifact(85)->build();
        $previous_changeset = ChangesetTestBuilder::aChangeset(865)->build();
        $submitter          = UserTestBuilder::buildWithDefaults();

        $saver = new ChangesetValueSaver();
        return $saver->saveNewChangesetForField(
            $this->field,
            $artifact,
            $previous_changeset,
            $this->fields_data,
            $submitter,
            self::NEW_CHANGESET_ID,
            $this->workflow,
            new CreatedFileURLMapping()
        );
    }

    public function testItSavesSubmittedFieldThatUserCanUpdate(): void
    {
        $this->fields_data[self::FIELD_ID] = self::FIELD_VALUE;
        $this->field->method('userCanUpdate')->willReturn(true);

        $this->field->expects($this->once())
            ->method('saveNewChangeset')
            ->with(
                self::isInstanceOf(Artifact::class),
                self::isInstanceOf(\Tracker_Artifact_Changeset::class),
                self::NEW_CHANGESET_ID,
                self::FIELD_VALUE,
                self::isInstanceOf(\PFUser::class),
                false,
                false,
                self::isInstanceOf(CreatedFileURLMapping::class)
            )
            ->willReturn(true);
        self::assertTrue($this->save());
    }

    public function testItBypassesPermissionWhenWorkflowTellsItTo(): void
    {
        $this->fields_data[self::FIELD_ID] = self::FIELD_VALUE;
        $this->field->method('userCanUpdate')->willReturn(false);
        $this->workflow->method('bypassPermissions')->willReturn(true);

        $this->field->expects($this->once())
            ->method('saveNewChangeset')
            ->with(
                self::isInstanceOf(Artifact::class),
                self::isInstanceOf(\Tracker_Artifact_Changeset::class),
                self::NEW_CHANGESET_ID,
                self::FIELD_VALUE,
                self::isInstanceOf(\PFUser::class),
                false,
                true,
                self::isInstanceOf(CreatedFileURLMapping::class)
            )
            ->willReturn(true);
        self::assertTrue($this->save());
    }

    public function testItSavesSubmittedFieldThatUserCannotUpdateWithNullValue(): void
    {
        $this->fields_data[self::FIELD_ID] = self::FIELD_VALUE;
        $this->field->method('userCanUpdate')->willReturn(false);
        $this->workflow->method('bypassPermissions')->willReturn(false);

        $this->field->expects($this->once())
            ->method('saveNewChangeset')
            ->with(
                self::isInstanceOf(Artifact::class),
                self::isInstanceOf(\Tracker_Artifact_Changeset::class),
                self::NEW_CHANGESET_ID,
                null,
                self::isInstanceOf(\PFUser::class),
                false,
                false,
                self::isInstanceOf(CreatedFileURLMapping::class)
            )
            ->willReturn(true);
        self::assertTrue($this->save());
    }

    public function testItSavesFieldThatWasNotSubmittedWithNullValue(): void
    {
        $this->field->expects($this->once())
            ->method('saveNewChangeset')
            ->with(
                self::isInstanceOf(Artifact::class),
                self::isInstanceOf(\Tracker_Artifact_Changeset::class),
                self::NEW_CHANGESET_ID,
                null,
                self::isInstanceOf(\PFUser::class),
                false,
                false,
                self::isInstanceOf(CreatedFileURLMapping::class)
            )
            ->willReturn(true);
        self::assertTrue($this->save());
    }

    public function testItReturnsFalseIfFieldCannotSaveChangesetValue(): void
    {
        $this->field->method('saveNewChangeset')->willReturn(false);
        self::assertFalse($this->save());
    }
}
