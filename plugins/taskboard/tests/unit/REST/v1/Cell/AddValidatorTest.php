<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1\Cell;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class AddValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddValidator $validator;
    private PFUser $current_user;
    private Artifact $swimlane_artifact;

    protected function setUp(): void
    {
        $this->swimlane_artifact = ArtifactTestBuilder::anArtifact(21)->build();
        $this->current_user      = UserTestBuilder::aUser()->build();
        $this->validator         = new AddValidator();
    }

    public function testValidateThrowsWhenArtifactToAddIsNotSoloItemAndHasNoParent(): void
    {
        $artifact_to_add = $this->mockArtifact(456);
        $artifact_to_add->expects(self::once())
            ->method('getParent')
            ->willReturn(null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->validate($artifact_to_add);
    }

    public function testValidateThrowsWhenArtifactToAddIsNeitherSoloItemNorChildOfSwimlane(): void
    {
        $artifact_to_add = $this->mockArtifact(456);
        $other_parent    = ArtifactTestBuilder::anArtifact(42)->build();
        $artifact_to_add
            ->expects(self::once())
            ->method('getParent')
            ->willReturn($other_parent);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->validate($artifact_to_add);
    }

    public function testValidateSucceedsForChildOfSwimlane(): void
    {
        $artifact_to_add = $this->mockArtifactWithParent(456);
        $this->validate($artifact_to_add);
    }

    public function testValidateSucceedsForSoloItem(): void
    {
        $this->expectNotToPerformAssertions();
        $this->swimlane_artifact = $this->mockArtifact(25);
        $this->validate($this->swimlane_artifact);
    }

    private function validate(Artifact $artifact_to_add): void
    {
        $this->validator->validateArtifacts($this->swimlane_artifact, $artifact_to_add, $this->current_user);
    }

    private function mockArtifact(int $id): MockObject&Artifact
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn($id);
        return $artifact;
    }

    private function mockArtifactWithParent(int $id): MockObject&Artifact
    {
        $artifact = $this->mockArtifact($id);
        $artifact->expects(self::once())
            ->method('getParent')
            ->willReturn($this->swimlane_artifact);
        return $artifact;
    }
}
