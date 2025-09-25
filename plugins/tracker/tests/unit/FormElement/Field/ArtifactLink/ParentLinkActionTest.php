<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ParentLinkActionTest extends TestCase
{
    private ParentLinkAction $action;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Artifact&MockObject $artifact;
    private PFUser $user;
    private Artifact&MockObject $parent_artifact;
    private Artifact&MockObject $another_parent_artifact;
    private ArtifactLinkField $artifact_link_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $this->action = new ParentLinkAction($this->artifact_factory);

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getId')->willReturn(101);

        $this->parent_artifact         = $this->createMock(Artifact::class);
        $this->another_parent_artifact = $this->createMock(Artifact::class);
        $this->user                    = UserTestBuilder::buildWithDefaults();

        $this->artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(587)->build();
    }

    public function testItReturnsTrueIfAtLeastOneLinkIsDone(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011', '1012'],
            ],
        ];

        $this->artifact->expects($this->once())->method('getAnArtifactLinkField')
            ->with($this->user)->willReturn($this->artifact_link_field);

        $this->artifact_factory->expects($this->exactly(2))->method('getArtifactById')
            ->willReturnCallback(fn (int $id) => match ($id) {
                1011 => $this->parent_artifact,
                1012 => $this->another_parent_artifact,
            });

        $this->parent_artifact->expects($this->once())->method('linkArtifact')
            ->with(101, $this->user, ArtifactLinkField::TYPE_IS_CHILD)->willReturn(true);

        $this->another_parent_artifact->expects($this->once())->method('linkArtifact')
            ->with(101, $this->user, ArtifactLinkField::TYPE_IS_CHILD)->willReturn(false);

        self::assertTrue(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfNoLinkCouldBeDone(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011', '1012'],
            ],
        ];

        $this->artifact->expects($this->once())->method('getAnArtifactLinkField')
            ->with($this->user)->willReturn($this->artifact_link_field);

        $this->artifact_factory->expects($this->exactly(2))->method('getArtifactById')
            ->willReturnCallback(fn (int $id) => match ($id) {
                1011 => $this->parent_artifact,
                1012 => $this->another_parent_artifact,
            });

        $this->parent_artifact->expects($this->once())->method('linkArtifact')
            ->with(101, $this->user, ArtifactLinkField::TYPE_IS_CHILD)->willReturn(false);

        $this->another_parent_artifact->expects($this->once())->method('linkArtifact')
            ->with(101, $this->user, ArtifactLinkField::TYPE_IS_CHILD)->willReturn(false);

        self::assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfNoArtifactLinkFieldForUser(): void
    {
        $fields_data = [
            587 => [
                'parent' => '1011',
            ],
        ];

        $this->artifact->expects($this->once())->method('getAnArtifactLinkField')->with($this->user)->willReturn(null);

        $this->parent_artifact->expects($this->never())->method('linkArtifact');

        self::assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfFieldsDataIsNotWellFormed(): void
    {
        $fields_data = [
            587 => [
                'whatever' => '1011',
            ],
        ];

        $this->artifact->method('getAnArtifactLinkField')->with($this->user)->willReturn($this->artifact_link_field);

        $this->parent_artifact->expects($this->never())->method('linkArtifact');

        self::assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );

        $fields_data = [
            'parent' => ['1011'],
        ];

        self::assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }

    public function testItReturnsFalseIfParentArtifactNotFound(): void
    {
        $fields_data = [
            587 => [
                'parent' => ['1011'],
            ],
        ];

        $this->artifact->expects($this->once())->method('getAnArtifactLinkField')
            ->with($this->user)->willReturn($this->artifact_link_field);

        $this->artifact_factory->expects($this->once())->method('getArtifactById')->with(1011)->willReturn(null);

        $this->parent_artifact->expects($this->never())->method('linkArtifact');

        self::assertFalse(
            $this->action->linkParent(
                $this->artifact,
                $this->user,
                $fields_data
            )
        );
    }
}
