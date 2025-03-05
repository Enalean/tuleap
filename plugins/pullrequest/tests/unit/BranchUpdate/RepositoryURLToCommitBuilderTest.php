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

namespace Tuleap\PullRequest\BranchUpdate;

use Git_GitRepositoryUrlManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RepositoryURLToCommitBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsURLToCommitForASpecificGitRepository(): void
    {
        $repository  = $this->createMock(\GitRepository::class);
        $url_manager = $this->createMock(Git_GitRepositoryUrlManager::class);

        $builder = new RepositoryURLToCommitBuilder($url_manager, $repository);

        $url_manager->method('getAbsoluteCommitURL')->with($repository, self::anything())
            ->willReturn('https://example.com/my-commit-link');

        self::assertEquals(
            'https://example.com/my-commit-link',
            $builder->buildURLForReference('0000000000000000000000000000000000000000')
        );
    }
}
