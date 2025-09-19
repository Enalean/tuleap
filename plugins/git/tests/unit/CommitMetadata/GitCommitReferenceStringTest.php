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

namespace Tuleap\Git\CommitMetadata;

use Tuleap\Git\Hook\DefaultBranchPush\CommitHash;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitCommitReferenceStringTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const string COMMIT_SHA1     = '130586a6';
    private const string REPOSITORY_PATH = 'vervel/corticipetally';

    private function getStringReference(): string
    {
        $hash = CommitHash::fromString(self::COMMIT_SHA1);

        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getFullName')->willReturn(self::REPOSITORY_PATH);

        $reference = GitCommitReferenceString::fromRepositoryAndCommit($git_repository, $hash);
        return $reference->getStringReference();
    }

    public function testItBuildsFromRepositoryAndCommitHash(): void
    {
        self::assertSame(
            sprintf(
                '%s #%s/%s',
                \Git::REFERENCE_KEYWORD,
                self::REPOSITORY_PATH,
                self::COMMIT_SHA1
            ),
            $this->getStringReference()
        );
    }
}
