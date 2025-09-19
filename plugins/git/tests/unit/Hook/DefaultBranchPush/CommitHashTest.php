<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\DefaultBranchPush;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitHashTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const string COMMIT_SHA1 = '021edd5653';

    public function testItBuildsFromSha1String(): void
    {
        $hash = CommitHash::fromString(self::COMMIT_SHA1);
        self::assertSame(self::COMMIT_SHA1, (string) $hash);
    }
}
