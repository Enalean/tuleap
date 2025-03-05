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

namespace Tuleap\PullRequest\Reviewer\Autocompleter;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UsernameToSearchTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNameToSearchWithALongEnoughLength(): void
    {
        $name = 'aaaaaaaaa';

        $username_to_search = UsernameToSearch::fromString($name);

        $this->assertEquals($name, $username_to_search->toString());
    }

    public function testNameToSearchWithASmallLengthAreRejected(): void
    {
        $this->expectException(UsernameToSearchTooSmallException::class);
        UsernameToSearch::fromString('a');
    }
}
