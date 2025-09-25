<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

use Tracker_FieldValueNotStoredException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\ChangesetValue\SaveChangesetValueStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewChangesetFieldValueSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Workflow&\PHPUnit\Framework\MockObject\MockObject $workflow;
    private \PFUser $user;
    private NewChangeset $new_changeset;
    private FieldsToBeSavedInSpecificOrderRetriever&\PHPUnit\Framework\MockObject\Stub $fields_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->user             = UserTestBuilder::anActiveUser()->build();
        $this->fields_retriever = $this->createStub(FieldsToBeSavedInSpecificOrderRetriever::class);
        $this->new_changeset    = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            ArtifactTestBuilder::anArtifact(1)->build(),
            [],
            $this->user,
            1234567890,
        );
        $this->workflow         = $this->createMock(\Workflow::class);
    }

    public function testItSavesFields(): void
    {
        $changeset_value_saver = SaveChangesetValueStub::buildStoreField();
        $saver                 = new NewChangesetFieldValueSaver($this->fields_retriever, $changeset_value_saver);

        $this->fields_retriever->method('getFields')->willReturn(
            [TextFieldBuilder::aTextField(123)->build()]
        );

        $saver->storeFieldsValues(
            $this->new_changeset,
            null,
            [],
            1,
            $this->workflow
        );

        self::assertEquals(1, $changeset_value_saver->getCount());
    }

    public function testItThrowsOnError(): void
    {
        $changeset_value_saver = SaveChangesetValueStub::buildFail();
        $saver                 = new NewChangesetFieldValueSaver($this->fields_retriever, $changeset_value_saver);

        $this->fields_retriever->method('getFields')->willReturn(
            [TextFieldBuilder::aTextField(123)->build()]
        );

        $this->expectException(Tracker_FieldValueNotStoredException::class);
        $saver->storeFieldsValues(
            $this->new_changeset,
            null,
            [],
            1,
            $this->workflow
        );
    }
}
