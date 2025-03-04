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

namespace Tuleap\ProgramManagement\Domain\Events;

use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PendingIterationCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 242;
    private const CHANGESET_ID = 5945;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->iteration_verifier  = VerifyIsIterationStub::withValidIteration();
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->changeset_verifier  = VerifyIsChangesetStub::withValidChangeset();
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();
    }

    public function testItBuildsFromIds(): void
    {
        $creation = PendingIterationCreation::fromIds(
            $this->iteration_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            self::ITERATION_ID,
            self::CHANGESET_ID,
            $this->user_identifier
        );
        self::assertSame(self::ITERATION_ID, $creation?->getIteration()->getId());
        self::assertSame(self::CHANGESET_ID, $creation?->getChangeset()->getId());
    }

    public function testItReturnsNullWhenGivenIdIsNotAnIteration(): void
    {
        self::assertNull(
            PendingIterationCreation::fromIds(
                VerifyIsIterationStub::withNotIteration(),
                $this->visibility_verifier,
                $this->changeset_verifier,
                self::ITERATION_ID,
                self::CHANGESET_ID,
                $this->user_identifier
            )
        );
    }

    public function testItReturnsNullWhenGivenIdIsNotAChangeset(): void
    {
        self::assertNull(
            PendingIterationCreation::fromIds(
                $this->iteration_verifier,
                $this->visibility_verifier,
                VerifyIsChangesetStub::withNotValidChangeset(),
                self::ITERATION_ID,
                self::CHANGESET_ID,
                $this->user_identifier
            )
        );
    }
}
