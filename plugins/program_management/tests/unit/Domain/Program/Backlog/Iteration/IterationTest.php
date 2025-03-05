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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAnIterationFromArtifact(): void
    {
        $iteration = Iteration::build(
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            RetrieveTitleValueUserCanSeeStub::withValue('My artifact'),
            RetrieveTimeframeValueUserCanSeeStub::withValues(1635412289, 1635868368),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withAllowed(),
            UserIdentifierStub::buildGenericUser(),
            IterationIdentifierBuilder::buildWithId(1),
        );

        self::assertEquals(1, $iteration?->id);
        self::assertEquals(1635412289, $iteration?->start_date);
        self::assertEquals(1635868368, $iteration?->end_date);
        self::assertEquals('My artifact', $iteration?->title);
        self::assertEquals('On going', $iteration?->status);
        self::assertEquals('art #1', $iteration?->cross_ref);
        self::assertEquals('/plugins/tracker/?aid=1', $iteration?->uri);
        self::assertEquals(true, $iteration?->user_can_update);
    }
}
