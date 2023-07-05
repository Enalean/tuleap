<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Hudson\Reference;

use ProjectManager;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class HudsonCrossReferenceOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HudsonCrossReferenceOrganizer $organizer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CrossReferenceByNatureOrganizer
     */
    private $organizer_by_nature;
    private \Project $project;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->organizer_by_nature = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $this->user                = $this->createMock(\PFUser::class);

        $this->organizer = new HudsonCrossReferenceOrganizer($this->project_manager);
    }

    public function testItDontMoveCrossReferenceIfNotHudson(): void
    {
        $this->organizer_by_nature
            ->method("getCrossReferencePresenters")
            ->willReturn([CrossReferencePresenterBuilder::get(1)->withType('git')->build()]);

        $this->organizer_by_nature
            ->expects(self::never())
            ->method("removeUnreadableCrossReference");

        $this->organizer_by_nature
            ->expects(self::never())
            ->method("moveCrossReferenceToSection");

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }

    public function testItRemoveHudsonCrossReferenceIfUserIsNotMemberOfProject(): void
    {
        $ref_build = CrossReferencePresenterBuilder::get(1)->withType("hudson_build")->withValue('23')->withProjectId(101)->build();
        $ref_job   = CrossReferencePresenterBuilder::get(1)->withType("hudson_job")->withValue('MyJob')->withProjectId(101)->build();

        $this->organizer_by_nature
            ->expects(self::once())
            ->method("getCrossReferencePresenters")
            ->willReturn([$ref_build, $ref_job]);

        $this->user->expects(self::exactly(2))->method('isMember')->with(101)->willReturn(false);

        $this->organizer_by_nature
            ->method("getCurrentUser")
            ->willReturn($this->user);

        $this->project_manager->expects(self::exactly(2))->method("getProject")->willReturn($this->project);

        $this->organizer_by_nature
            ->method("removeUnreadableCrossReference")
            ->withConsecutive([$ref_build], [$ref_job]);

        $this->organizer_by_nature
            ->expects(self::never())
            ->method("moveCrossReferenceToSection");

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }

    public function testItMoveHudsonCrossReferenceInUnlabelledSection(): void
    {
        $ref_build = CrossReferencePresenterBuilder::get(1)->withType("hudson_build")->withValue('23')->withProjectId(101)->build();
        $ref_job   = CrossReferencePresenterBuilder::get(1)->withType("hudson_job")->withValue('MyJob')->withProjectId(101)->build();

        $this->organizer_by_nature
            ->method("getCrossReferencePresenters")
            ->willReturn([$ref_build, $ref_job]);

        $this->user->expects(self::exactly(2))->method('isMember')->with(101)->willReturn(true);

        $this->organizer_by_nature
            ->method("getCurrentUser")
            ->willReturn($this->user);

        $this->project_manager->expects(self::exactly(2))->method("getProject")->willReturn($this->project);

        $this->organizer_by_nature
            ->expects(self::never())
            ->method("removeUnreadableCrossReference");

        $this->organizer_by_nature
            ->method("moveCrossReferenceToSection")
            ->withConsecutive(
                [$ref_build, ""],
                [$ref_job, ""],
            );

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }
}
