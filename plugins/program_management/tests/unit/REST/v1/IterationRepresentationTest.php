<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Iteration;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsRepresentationFromIteration(): void
    {
        $iteration = Iteration::build(
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            RetrieveTitleValueUserCanSeeStub::withValue('An iteration'),
            RetrieveTimeframeValueUserCanSeeStub::withValues(1633189968, 1635868368),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withAllowed(),
            UserIdentifierStub::buildGenericUser(),
            IterationIdentifierBuilder::buildWithId(1),
        );

        assert($iteration instanceof Iteration);
        $representation = IterationRepresentation::buildFromIteration($iteration);

        self::assertEquals($representation->id, $iteration->id);
    }
}
