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
use Tuleap\Tracker\Artifact\Closure\ClosingKeyword;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Closure\ArtifactClosingCommentInCommonMarkFormat;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PostPushArtifactCommentTest extends TestCase
{
    private const ARTIFACT_ID                   = 12;
    private const COMMIT_SHA1                   = '614b83';
    private const USERNAME_CLOSING_THE_ARTIFACT = 'lgilhooly';
    private const GITLAB_REPOSITORY_NAME        = 'MyGitlabRepo';
    private const TRACKER_SHORTNAME             = 'tracker_isetta';

    private function buildComment(WebhookTuleapReference $reference): ArtifactClosingCommentInCommonMarkFormat
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

        $tracker  = TrackerTestBuilder::aTracker()->withShortName(self::TRACKER_SHORTNAME)->build();
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($tracker)->build();

        return PostPushArtifactComment::fromCommit(
            self::USERNAME_CLOSING_THE_ARTIFACT,
            $commit,
            $reference,
            $integration,
            $artifact
        );
    }

    public function dataProviderReference(): iterable
    {
        return [
            'empty comment when keyword is null' => [null, ''],
            'comment with resolves'              => [
                ClosingKeyword::buildResolves(),
                sprintf(
                    'solved by %1$s with %2$s #%3$s/%4$s',
                    self::USERNAME_CLOSING_THE_ARTIFACT,
                    GitlabCommitReference::REFERENCE_NAME,
                    self::GITLAB_REPOSITORY_NAME,
                    self::COMMIT_SHA1,
                )],
            'comment with closes'                => [
                ClosingKeyword::buildCloses(),
                sprintf(
                    'closed by %1$s with %2$s #%3$s/%4$s',
                    self::USERNAME_CLOSING_THE_ARTIFACT,
                    GitlabCommitReference::REFERENCE_NAME,
                    self::GITLAB_REPOSITORY_NAME,
                    self::COMMIT_SHA1,
                )],
            'comment with implements'            => [
                ClosingKeyword::buildImplements(),
                sprintf(
                    'implemented by %1$s with %2$s #%3$s/%4$s',
                    self::USERNAME_CLOSING_THE_ARTIFACT,
                    GitlabCommitReference::REFERENCE_NAME,
                    self::GITLAB_REPOSITORY_NAME,
                    self::COMMIT_SHA1,
                )],
            'comments with fixes'                => [
                ClosingKeyword::buildFixes(),
                sprintf(
                    '%1$s fixed by %2$s with %3$s #%4$s/%5$s',
                    self::TRACKER_SHORTNAME,
                    self::USERNAME_CLOSING_THE_ARTIFACT,
                    GitlabCommitReference::REFERENCE_NAME,
                    self::GITLAB_REPOSITORY_NAME,
                    self::COMMIT_SHA1,
                ),
            ],
        ];
    }

    /**
     * @dataProvider dataProviderReference
     */
    public function testItBuildsComment(?ClosingKeyword $keyword, string $expected_comment): void
    {
        $reference = new WebhookTuleapReference(self::ARTIFACT_ID, $keyword);

        $comment = $this->buildComment($reference);

        self::assertSame($expected_comment, $comment->getBody());
    }
}
