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
use Mockery;
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
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

class PostPushCommitArtifactUpdaterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PostPushCommitArtifactUpdater
     */
    private $updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValueRetriever
     */
    private $status_value_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitWebhookData
     */
    private $webhook_data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->status_value_retriever = Mockery::mock(StatusValueRetriever::class);
        $this->user_manager           = Mockery::mock(UserManager::class);

        $this->updater = new PostPushCommitArtifactUpdater(
            $this->status_value_retriever,
            $this->user_manager,
            new NullLogger()
        );

        $this->webhook_data = Mockery::mock(PostPushCommitWebhookData::class);
    }

    public function testItDoesNotAddArtifactCommentWithoutStatusUpdatedIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $message  = "@asticotc attempts to close this artifact from GitLab but no status semantic defined.";

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andThrow(Tracker_NoChangeException::class);

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItDoesNotAddArtifactCommentWithoutStatusUpdatedIfTheCommentIsNotCreated(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $message  = "@asticotc attempts to close this artifact from GitLab but no status semantic defined.";

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturnNull();

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheTuleapUsernameIfTheTuleapUserExists(): void
    {
        $artifact = Mockery::mock(Artifact::class);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $message = "@asticotc attempts to close this artifact from GitLab but no status semantic defined.";
        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturn(Mockery::mock(Tracker_Artifact_Changeset::class));

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithoutStatusUpdatedWithTheGitlabCommitterAuthorIfTheTuleapUserDoesNotExist(): void
    {
        $artifact = Mockery::mock(Artifact::class);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
            ]
        );

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldReceive("getAuthorName")->andReturn("Coco L'Asticot");
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturnNull();

        $message = "Coco L'Asticot attempts to close this artifact from GitLab but no status semantic defined.";
        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturn(Mockery::mock(Tracker_Artifact_Changeset::class));

        $this->updater->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItDoesNotAddArtifactCommentAndUpdateStatusIfAnErrorOccursDuringTheCommentCreation(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->build();
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('isOpen')->andReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->once();

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
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

        $status_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->once()->andReturn(18);
        $status_field->shouldReceive('getFieldData')->once()->with("Done")->andReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $this->status_value_retriever->shouldReceive("getFirstClosedValueUserCanRead")
            ->once()
            ->with($tracker, $tracker_workflow_user)
            ->andReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->shouldReceive("createNewChangeset")->with([18 => 1234], $message, $tracker_workflow_user)->andThrow(Tracker_NoChangeException::class);

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
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('isOpen')->andReturn(true);
        $message = 'solved by @asticotc with gitlab_commit #MyRepo/azer12563';

        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->once();

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
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

        $status_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->once()->andReturn(18);
        $status_field->shouldReceive('getFieldData')->once()->with("Done")->andReturn(1234);

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $this->status_value_retriever->shouldReceive("getFirstClosedValueUserCanRead")
            ->once()
            ->with($tracker, $tracker_workflow_user)
            ->andReturn(new Tracker_FormElement_Field_List_Bind_StaticValue(14, "Done", "", 1, false));

        $artifact->shouldReceive("createNewChangeset")->with([18 => 1234], $message, $tracker_workflow_user)->andReturnNull();

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
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('isOpen')->andReturn(true);
        $message = '@asticotc attempts to close this artifact from GitLab but no status semantic defined.';

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
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

        $status_field = Mockery::mock(\Tracker_FormElement_Field_List::class);

        $committer_email = "committer@example.com";
        $this->webhook_data->shouldReceive("getAuthorEmail")->andReturn($committer_email);
        $this->webhook_data->shouldNotReceive("getAuthorName");
        $committer = new PFUser(
            [
                "user_id"   => 102,
                "email"     => "mail@example.com",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $this->status_value_retriever->shouldReceive("getFirstClosedValueUserCanRead")
            ->once()
            ->with($tracker, $tracker_workflow_user)
            ->andThrow(SemanticStatusClosedValueNotFoundException::class);

        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturnNull();

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
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->once();

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('isOpen')->andReturn(false);
        $artifact->shouldReceive('getId')->andReturn(25);

        $tracker_workflow_user = new Tracker_Workflow_WorkflowUser(
            [
                "user_id" => Tracker_Workflow_WorkflowUser::ID,
                'language_id' => 'en'
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

        $status_field = Mockery::mock(\Tracker_FormElement_Field_List::class);

        $artifact->shouldNotReceive("createNewChangeset");

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
