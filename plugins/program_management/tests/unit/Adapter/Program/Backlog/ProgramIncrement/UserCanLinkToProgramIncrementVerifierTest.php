<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactLinkFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanLinkToProgramIncrementVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID = 477;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField
     */
    private $field;
    private RetrieveFullArtifactLinkFieldStub $field_retriever;
    private ProgramIncrementTrackerIdentifier $program_increment_tracker;
    private UserIdentifierStub $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->field                     = $this->createStub(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $this->field_retriever           = RetrieveFullArtifactLinkFieldStub::withField($this->field);
        $this->program_increment_tracker = ProgramIncrementTrackerIdentifierBuilder::buildWithId(48);
        $this->user                      = UserIdentifierStub::withId(self::USER_ID);
    }

    private function getVerifier(): UserCanLinkToProgramIncrementVerifier
    {
        return new UserCanLinkToProgramIncrementVerifier(
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(self::USER_ID)),
            $this->field_retriever
        );
    }

    public function testItReturnsTrueWhenUserCanUpdate(): void
    {
        $this->field->method('userCanUpdate')->willReturn(true);
        self::assertTrue(
            $this->getVerifier()->canUserLinkToProgramIncrement($this->program_increment_tracker, $this->user)
        );
    }

    public function testItReturnsFalseWhenUserCannotUpdate(): void
    {
        $this->field->method('userCanUpdate')->willReturn(false);
        self::assertFalse(
            $this->getVerifier()->canUserLinkToProgramIncrement($this->program_increment_tracker, $this->user)
        );
    }

    public function testItReturnsFalseWhenNoArtifactLinkField(): void
    {
        $this->field_retriever = RetrieveFullArtifactLinkFieldStub::withNoField();
        self::assertFalse(
            $this->getVerifier()->canUserLinkToProgramIncrement($this->program_increment_tracker, $this->user)
        );
    }
}
