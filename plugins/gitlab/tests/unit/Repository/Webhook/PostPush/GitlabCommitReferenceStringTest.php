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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabCommitReferenceStringTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const string REPOSITORY_NAME = 'numerably';
    private const string COMMIT_SHA1     = '990e5954';

    private function getStringReference(): string
    {
        $repository = new GitlabRepositoryIntegration(
            98,
            73,
            self::REPOSITORY_NAME,
            'Irrelevant',
            'https://example.com',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            true,
        );

        $commit = new PostPushCommitWebhookData(
            self::COMMIT_SHA1,
            'Irrelevant',
            'Irrelevant',
            'main',
            1716084946,
            'user@example.com',
            'Irrelevant'
        );

        $reference = GitlabCommitReferenceString::fromRepositoryAndCommit($repository, $commit);
        return $reference->getStringReference();
    }

    public function testItBuildsFromRepositoryAndCommit(): void
    {
        $reference = sprintf(
            '%s #%s/%s',
            GitlabCommitReference::REFERENCE_NAME,
            self::REPOSITORY_NAME,
            self::COMMIT_SHA1
        );
        self::assertSame($reference, $this->getStringReference());
    }
}
