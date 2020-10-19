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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Artifact\Artifact;

final class AddValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AddValidator */
    private $validator;
    /** @var M\LegacyMockInterface|M\MockInterface|PFUser */
    private $current_user;
    /** @var M\LegacyMockInterface|M\MockInterface|Artifact */
    private $swimlane_artifact;

    protected function setUp(): void
    {
        $this->swimlane_artifact = $this->mockArtifact(21);
        $this->current_user      = M::mock(PFUser::class);
        $this->validator         = new AddValidator();
    }

    public function testValidateThrowsWhenArtifactToAddIsNotSoloItemAndHasNoParent(): void
    {
        $artifact_to_add = $this->mockArtifact(456);
        $artifact_to_add->shouldReceive('getParent')
            ->once()
            ->andReturnNull();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->validate($artifact_to_add);
    }

    public function testValidateThrowsWhenArtifactToAddIsNeitherSoloItemNorChildOfSwimlane(): void
    {
        $artifact_to_add = $this->mockArtifact(456);
        $other_parent       = $this->mockArtifact(42);
        $artifact_to_add->shouldReceive('getParent')
            ->once()
            ->andReturn($other_parent);

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
        $this->swimlane_artifact = $this->mockArtifact(25);
        $this->validate($this->swimlane_artifact);
    }

    private function validate(Artifact $artifact_to_add): void
    {
        $this->validator->validateArtifacts($this->swimlane_artifact, $artifact_to_add, $this->current_user);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Artifact
     */
    private function mockArtifact(int $id)
    {
        $artifact = M::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        return $artifact;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Artifact
     */
    private function mockArtifactWithParent(int $id)
    {
        $artifact = $this->mockArtifact($id);
        $artifact->shouldReceive('getParent')
            ->once()
            ->andReturn($this->swimlane_artifact);
        return $artifact;
    }
}
