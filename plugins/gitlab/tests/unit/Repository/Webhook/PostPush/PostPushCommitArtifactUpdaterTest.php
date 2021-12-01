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
use PFUser;
use Project;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_NoChangeException;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

class PostPushCommitArtifactUpdaterTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&StatusValueRetriever
     */
    private $status_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DoneValueRetriever
     */
    private $done_value_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushCommitWebhookData
     */
    private $webhook_data;

    private PostPushCommitArtifactUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->status_value_retriever = $this->createMock(StatusValueRetriever::class);
        $this->done_value_retriever   = $this->createMock(DoneValueRetriever::class);
        $this->user_manager           = $this->createMock(UserManager::class);

        $this->updater = new PostPushCommitArtifactUpdater(
            $this->status_value_retriever,
            $this->done_value_retriever,
            $this->user_manager,
            new NullLogger()
        );

        $this->webhook_data = $this->createMock(PostPushCommitWebhookData::class);
    }

    public function testItDoesNotAddArtifactCommentWithoutStatusUpdatedIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $message  = "@asticotc attempts to close this artifact from GitLab but neither done nor status semantic defined.";

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $artifact->method("createNewChangeset")
            ->with([], $message, $tracker_workflow_user)
            ->willThrowException(new Tracker_NoChangeException(1, 'xref'));

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItDoesNotAddArtifactCommentWithoutStatusUpdatedIfTheCommentIsNotCreated(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $message  = "@asticotc attempts to close this artifact from GitLab but neither done nor status semantic defined.";

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $artifact->method("createNewChangeset")->with([], $message, $tracker_workflow_user)->willReturn(null);

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheTuleapUsernameIfTheTuleapUserExists(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $message = "@asticotc attempts to close this artifact from GitLab but neither done nor status semantic defined.";
        $artifact->method("createNewChangeset")->with([], $message, $tracker_workflow_user)->willReturn($this->createMock(Tracker_Artifact_Changeset::class));

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheGitlabCommitterAuthorIfTheTuleapUserDoesNotExist(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->method("getAuthorName")->willReturn("Coco L'Asticot");
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn(null);

        $message = "Coco L'Asticot attempts to close this artifact from GitLab but neither done nor status semantic defined.";
        $artifact
            ->expects(self::once())
            ->method("createNewChangeset")
            ->with([], $message, $tracker_workflow_user)
            ->willReturn($this->createMock(Tracker_Artifact_Changeset::class));

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $artifact->method('isOpen')->willReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->expects(self::once())
            ->method("getSha1")
            ->willReturn("azer12563");

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolves");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(18);

        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->with("Done")
            ->willReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->method("createNewChangeset")
            ->with([18 => 1234], $message, $tracker_workflow_user)
            ->willThrowException(new Tracker_NoChangeException(1, 'xref'));

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfCommentIsNotCreated(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $artifact->method('isOpen')->willReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->expects(self::once())
            ->method("getSha1")
            ->willReturn("azer12563");

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolves");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(18);
        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->with("Done")
            ->willReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->method("createNewChangeset")->with([18 => 1234], $message, $tracker_workflow_user)->willReturn(null);

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItAddArtifactCommentWithoutStatusUpdatedIfNotCloseStatusSemanticDefined(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $artifact->method('isOpen')->willReturn(true);
        $message = '@asticotc attempts to close this artifact from GitLab but neither done nor status semantic defined.';

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolves");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willThrowException(new SemanticDoneValueNotFoundException());

        $this->status_value_retriever
            ->expects(self::once())
            ->method("getFirstClosedValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willThrowException(new SemanticStatusClosedValueNotFoundException());

        $artifact->method("createNewChangeset")->with([], $message, $tracker_workflow_user)->willReturn(null);

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItDoesNothingIfArtifactIsAlreadyClosed(): void
    {
        $this->webhook_data
            ->expects(self::once())
            ->method("getSha1")
            ->willReturn("azer12563");

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('isOpen')->willReturn(false);
        $artifact->method('getId')->willReturn(25);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolve");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);

        $artifact->expects(self::never())->method("createNewChangeset");

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItClosesArtifactWithDoneValue(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $artifact->method('isOpen')->willReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->expects(self::once())
            ->method("getSha1")
            ->willReturn("azer12563");

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolves");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(18);
        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->with("Done")
            ->willReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->method("createNewChangeset")->with([18 => 1234], $message, $tracker_workflow_user)
            ->willReturn(
                $this->createMock(Tracker_Artifact_Changeset::class)
            );

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }

    public function testItClosesArtifactWithFirstClosedStatusValue(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $artifact->method('isOpen')->willReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->expects(self::once())
            ->method("getSha1")
            ->willReturn("azer12563");

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en',
            ]
        );

        $reference   = new WebhookTuleapReference(12, "resolves");
        $integration = new GitlabRepositoryIntegration(
            1,
            12,
            "MyRepo",
            "",
            "https://example",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $status_field
            ->expects(self::once())
            ->method('getId')
            ->willReturn(18);
        $status_field
            ->expects(self::once())
            ->method('getFieldData')
            ->with("Done")
            ->willReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->method("getAuthorEmail")->willReturn($committer_email);
        $this->webhook_data->expects(self::never())->method("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en',
            ]
        );
        $this->user_manager->method("getUserByEmail")->with($committer_email)->willReturn($committer);

        $this->done_value_retriever
            ->expects(self::once())
            ->method("getFirstDoneValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willThrowException(
                new SemanticDoneValueNotFoundException()
            );

        $this->status_value_retriever
            ->expects(self::once())
            ->method("getFirstClosedValueUserCanRead")
            ->with($tracker, $tracker_workflow_user)
            ->willReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->method("createNewChangeset")->with([18 => 1234], $message, $tracker_workflow_user)
            ->willReturn(
                $this->createMock(Tracker_Artifact_Changeset::class)
            );

        $this->updater->closeTuleapArtifact(
            $artifact,
            $tracker_workflow_user,
            $this->webhook_data,
            $reference,
            $status_field,
            $integration
        );
    }
}
