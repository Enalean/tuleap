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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\Branch;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BranchNameTest extends TestCase
{
    public function testAcceptsValidBranchName(): void
    {
        $branch_name = BranchName::fromBranchNameShortHand('dev');
        self::assertEquals('dev', $branch_name->name);
    }

    public function testRejectsInvalidBranchName(): void
    {
        $this->expectException(InvalidBranchNameException::class);
        BranchName::fromBranchNameShortHand('foo..bar');
    }

    public function testHasADefaultBranchName(): void
    {
        $default = BranchName::defaultBranchName();
        self::assertEquals('main', $default->name);
    }

    public function testDefaultBranchNameIsValid(): void
    {
        $default                        = BranchName::defaultBranchName();
        $branch_name_built_from_default = BranchName::fromBranchNameShortHand($default->name);

        self::assertEquals($default, $branch_name_built_from_default);
    }
}
