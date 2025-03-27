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
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueSaverIgnoringPermissionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID         = 643;
    private const NEW_CHANGESET_ID = 139;
    private const FIELD_VALUE      = 'trophodisc squawbush';
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject & \Tracker_FormElement_Field
     */
    private $field;
    private array $fields_data;

    protected function setUp(): void
    {
        $this->field = $this->createMock(\Tracker_FormElement_Field::class);
        $this->field->method('getId')->willReturn(self::FIELD_ID);
        $this->fields_data = [];
    }

    private function save(): bool
    {
        $artifact           = ArtifactTestBuilder::anArtifact(85)->build();
        $previous_changeset = ChangesetTestBuilder::aChangeset(865)->build();
        $submitter          = UserTestBuilder::buildWithDefaults();
        $workflow           = $this->createStub(\Workflow::class);

        $saver = new ChangesetValueSaverIgnoringPermissions();
        return $saver->saveNewChangesetForField(
            $this->field,
            $artifact,
            $previous_changeset,
            $this->fields_data,
            $submitter,
            self::NEW_CHANGESET_ID,
            $workflow,
            new CreatedFileURLMapping()
        );
    }

    public function testItBypassesPermissionsForSubmittedField(): void
    {
        $this->fields_data[self::FIELD_ID] = self::FIELD_VALUE;

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

    public function testItBypassesPermissionsForFieldThatWasNotSubmitted(): void
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
                true,
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
