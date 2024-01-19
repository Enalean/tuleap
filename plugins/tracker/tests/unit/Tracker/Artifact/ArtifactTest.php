<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ArtifactTest extends TestCase
{
    public function testUserCanViewCache(): void
    {
        $artifact = new Artifact(1, 2, 3, 4, false);

        $bob   = UserTestBuilder::anActiveUser()->withId(1)->build();
        $alice = UserTestBuilder::anActiveUser()->withId(2)->build();

        $artifact->setUserCanView([
            $bob->getId() => false,
            $alice->getId() => true,
        ]);

        self::assertFalse($artifact->userCanView($bob));
        self::assertTrue($artifact->userCanView($alice));
    }
}
