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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserCanPlanInProgramIncrementVerifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 16;
    private UserIdentifier $user;
    private RetrieveTitleValueUserCanSeeStub $title_retriever;
    private ProgramIncrementIdentifier $program_increment_identifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->title_retriever              = RetrieveTitleValueUserCanSeeStub::withValue('Increment 16');
        $this->user                         = UserIdentifierStub::buildGenericUser();
        $this->program_increment_identifier = ProgramIncrementIdentifierBuilder::buildWithIdAndUser(
            self::PROGRAM_INCREMENT_ID,
            $this->user
        );
    }

    private function getSearcher(): ProgramIncrementRetriever
    {
        return new ProgramIncrementRetriever(
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            $this->title_retriever,
            RetrieveTimeframeValueUserCanSeeStub::withValues(1633189968, 1635868368),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withAllowed(),
            UserCanPlanInProgramIncrementVerifierBuilder::buildWithAllowed()
        );
    }

    public function testItRetrievesProgramIncrementById(): void
    {
        $program_increment = $this->getSearcher()->retrieveProgramIncrementById(
            $this->user,
            $this->program_increment_identifier
        );
        self::assertNotNull($program_increment);
    }

    public function testItReturnsNullWhenUserCannotSeeProgramIncrementTitle(): void
    {
        $this->title_retriever = RetrieveTitleValueUserCanSeeStub::withoutValue();

        self::assertNull(
            $this->getSearcher()->retrieveProgramIncrementById(
                $this->user,
                $this->program_increment_identifier
            )
        );
    }
}
