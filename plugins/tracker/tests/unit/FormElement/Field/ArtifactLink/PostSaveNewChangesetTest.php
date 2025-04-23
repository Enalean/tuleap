<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class PostSaveNewChangesetTest extends TestCase
{
    public function testExecutesProcessChildrenTriggersCommand(): void
    {
        $artifact           = ArtifactTestBuilder::anArtifact(2541)->build();
        $user               = UserTestBuilder::buildWithDefaults();
        $new_changeset      = ChangesetTestBuilder::aChangeset(456)->build();
        $previous_changeset = null;
        $command            = $this->createMock(Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand::class);
        $field              = $this->createPartialMock(ArtifactLinkField::class, [
            'getProcessChildrenTriggersCommand',
            'getPostSaveNewChangesetLinkParentArtifact',
        ]);
        $field->method('getProcessChildrenTriggersCommand')->willReturn($command);
        $save_changeset = $this->createStub(PostSaveNewChangesetLinkParentArtifact::class);
        $field->method('getPostSaveNewChangesetLinkParentArtifact')->willReturn($save_changeset);
        $save_changeset->method('execute');

        $command->expects($this->once())->method('execute')->with($artifact, $user, $new_changeset, [], $previous_changeset);

        $field->postSaveNewChangeset($artifact, $user, $new_changeset, [], $previous_changeset);
    }
}
