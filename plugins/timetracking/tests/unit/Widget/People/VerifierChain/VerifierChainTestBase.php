<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\People\VerifierChain;

use Closure;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\VerifyInChainManagerIsAllowedToSeeTimesStub;
use Tuleap\Timetracking\Widget\People\TimeSpentInArtifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class VerifierChainTestBase extends TestCase
{
    /**
     * @param Closure(): VerifyInChainManagerIsAllowedToSeeTimes $build
     */
    protected function assertNextInChainDelegation(
        Closure $build,
        \PFUser $manager,
        TimeSpentInArtifact $time,
    ): void {
        $expectations = [
            [null, false],
            [VerifyInChainManagerIsAllowedToSeeTimesStub::withoutAllowed(), false],
            [VerifyInChainManagerIsAllowedToSeeTimesStub::withAllowed(), true],
        ];
        foreach ($expectations as [$next_in_chain, $expected]) {
            $verifier = $build();
            if ($next_in_chain) {
                $verifier->chain($next_in_chain);
            }

            self::assertSame(
                $expected,
                $verifier->isManagerAllowedToSeeTimes($time, $manager),
            );
            if ($next_in_chain) {
                self::assertTrue($next_in_chain->hasBeenCalled());
            }
        }
    }
}
