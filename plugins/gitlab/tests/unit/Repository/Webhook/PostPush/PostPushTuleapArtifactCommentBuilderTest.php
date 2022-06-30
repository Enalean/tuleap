<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use Project;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PostPushTuleapArtifactCommentBuilderTest extends TestCase
{
    private const ARTIFACT_ID                   = 12;
    private const COMMIT_SHA1                   = '614b83';
    private const USERNAME_CLOSING_THE_ARTIFACT = 'lgilhooly';
    private const GITLAB_REPOSITORY_NAME        = 'MyGitlabRepo';

    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->artifact = new Artifact(self::ARTIFACT_ID, 1, 101, 10050, false);
    }

    private function buildComment(WebhookTuleapReference $reference): string
    {
        $commit = new PostPushCommitWebhookData(
            self::COMMIT_SHA1,
            'commit',
            '',
            'branch_name',
            1620725174,
            'user@example.com',
            self::USERNAME_CLOSING_THE_ARTIFACT
        );

        $integration = new GitlabRepositoryIntegration(
            1,
            47,
            self::GITLAB_REPOSITORY_NAME,
            '',
            'https://example',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        return PostPushTuleapArtifactCommentBuilder::buildComment(
            self::USERNAME_CLOSING_THE_ARTIFACT,
            $commit,
            $reference,
            $integration,
            $this->artifact
        );
    }

    public function dataProviderReference(): iterable
    {
        return [
            'empty comment when keyword is null'        => [null, ''],
            'empty comment when keyword is not handled' => ['solved', ''],
            'comment with resolves'                     => ['resolves', sprintf(
                'solved by %1$s with %2$s #%3$s/%4$s',
                self::USERNAME_CLOSING_THE_ARTIFACT,
                GitlabCommitReference::REFERENCE_NAME,
                self::GITLAB_REPOSITORY_NAME,
                self::COMMIT_SHA1,
            )],
            'comment with closes'                       => ['closes', sprintf(
                'closed by %1$s with %2$s #%3$s/%4$s',
                self::USERNAME_CLOSING_THE_ARTIFACT,
                GitlabCommitReference::REFERENCE_NAME,
                self::GITLAB_REPOSITORY_NAME,
                self::COMMIT_SHA1,
            )],
            'comment with implements'                   => ['implements', sprintf(
                'implemented by %1$s with %2$s #%3$s/%4$s',
                self::USERNAME_CLOSING_THE_ARTIFACT,
                GitlabCommitReference::REFERENCE_NAME,
                self::GITLAB_REPOSITORY_NAME,
                self::COMMIT_SHA1,
            )],
        ];
    }

    /**
     * @dataProvider dataProviderReference
     */
    public function testWithoutTrackerShortname(?string $keyword, string $expected_comment): void
    {
        $reference = new WebhookTuleapReference(self::ARTIFACT_ID, $keyword);

        $comment = $this->buildComment($reference);

        self::assertSame($expected_comment, $comment);
    }

    public function testReturnCommentWhenKeywordIsFixes(): void
    {
        $reference = new WebhookTuleapReference(self::ARTIFACT_ID, 'fixes');

        $tracker_shortname = 'tracker_isetta';
        $tracker           = TrackerTestBuilder::aTracker()->withShortName($tracker_shortname)->build();
        $this->artifact    = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($tracker)->build();

        $comment = $this->buildComment($reference);
        self::assertSame(
            sprintf(
                '%1$s fixed by %2$s with %3$s #%4$s/%5$s',
                $tracker_shortname,
                self::USERNAME_CLOSING_THE_ARTIFACT,
                GitlabCommitReference::REFERENCE_NAME,
                self::GITLAB_REPOSITORY_NAME,
                self::COMMIT_SHA1,
            ),
            $comment
        );
    }
}
