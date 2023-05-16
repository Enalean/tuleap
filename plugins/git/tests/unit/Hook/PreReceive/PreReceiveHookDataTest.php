<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use Psr\Log\NullLogger;
use Tuleap\NeverThrow\Result;

class PreReceiveHookDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function wrongNumberOfRefsTest(): void
    {
        $input          = "a b c\n\rd e";
        $git_dir_path   = "/repo-git";
        $guest_dir_path = "/repo-git-guest";
        $result         = PreReceiveHookData::fromRawStdinHook($input, $git_dir_path, $guest_dir_path, new NullLogger());
        self::assertTrue(Result::isErr($result));
        self::assertEquals("Wrong number of arguments submitted, three arguments of the form old_rev new_rev refname expected on STDIN", (string) $result->error);
    }

    public function testNormalBehaviour(): void
    {
        $input                      = "a b ref1\n\rc d ref2";
        $git_dir_path               = "/git-repo";
        $guest_dir_path             = "/repo-git-guest";
        $updated_references         = [];
        $updated_references['ref1'] = new PreReceiveHookUpdatedReference('a', 'b');
        $updated_references['ref2'] = new PreReceiveHookUpdatedReference('c', 'd');

        $result = PreReceiveHookData::fromRawStdinHook($input, $git_dir_path, $guest_dir_path, new NullLogger());
        self::assertTrue(Result::isOk($result));
        self::assertEquals($updated_references, $result->value->updated_references);
    }
}
