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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostSaveNewChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecutesProcessChildrenTriggersCommand(): void
    {
        $artifact           = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $user               = Mockery::mock(\PFUser::class);
        $new_changeset      = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $previous_changeset = null;
        $command            = Mockery::spy(\Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand::class);
        $field              = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getProcessChildrenTriggersCommand')->andReturn($command);
        $field->shouldReceive('getPostSaveNewChangesetLinkParentArtifact')->andReturn(
            new class (Mockery::mock(ParentLinkAction::class)) extends PostSaveNewChangesetLinkParentArtifact {
                public function execute(
                    Artifact $artifact,
                    PFUser $submitter,
                    Tracker_Artifact_Changeset $new_changeset,
                    array $fields_data,
                    ?Tracker_Artifact_Changeset $previous_changeset = null,
                ): void {
                    return;
                }
            }
        );

        $command->shouldReceive('execute')->with($artifact, $user, $new_changeset, [], $previous_changeset)->once();

        $field->postSaveNewChangeset($artifact, $user, $new_changeset, [], $previous_changeset);
    }
}
