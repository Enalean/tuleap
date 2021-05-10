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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\Log\LoggerInterface;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tracker_NoChangeException;
use Tracker_Workflow_WorkflowUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenter;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenterBuilder;
use Tuleap\Gitlab\Repository\Webhook\Bot\CommentSender;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\InvalidCredentialsNotifier;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Templating\TemplateCache;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

class PostPushCommitBotCommenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var PostPushCommitBotCommenter
     */
    private $commenter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $client_wrapper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostPushCommitWebhookData
     */
    private $webhook_data;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepository
     */
    private $gitlab_repository;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InstanceBaseURLBuilder
     */
    private $bot_comment_reference_presenter_builder;
    /**
     * @var TemplateRendererFactory
     */
    private $template_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactRetriever
     */
    private $artifact_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;


    protected function setUp(): void
    {
        parent::setUp();

        $this->client_wrapper                          = Mockery::mock(ClientWrapper::class);
        $this->credentials_retriever                   = Mockery::mock(CredentialsRetriever::class);
        $this->logger                                  = Mockery::mock(LoggerInterface::class);
        $this->webhook_data                            = Mockery::mock(PostPushCommitWebhookData::class);
        $this->gitlab_repository                       = Mockery::mock(GitlabRepository::class);
        $this->bot_comment_reference_presenter_builder = Mockery::mock(BotCommentReferencePresenterBuilder::class);
        $this->user_manager                            = Mockery::mock(UserManager::class);

        $template_cache         = \Mockery::mock(TemplateCache::class, ['getPath' => null]);
        $this->template_factory = new TemplateRendererFactory($template_cache);

        $this->commenter = new PostPushCommitBotCommenter(
            new CommentSender($this->client_wrapper, Mockery::mock(InvalidCredentialsNotifier::class)),
            $this->credentials_retriever,
            $this->logger,
            $this->bot_comment_reference_presenter_builder,
            $this->template_factory,
            $this->user_manager
        );
    }

    public function testNothingHappenIfNoReferences(): void
    {
        $this->credentials_retriever->shouldReceive('getCredentials')->never();
        $this->logger->shouldReceive('debug')->never();
        $this->client_wrapper->shouldReceive('postUrl')->never();

        $this->commenter->addCommentOnCommit($this->webhook_data, $this->gitlab_repository, []);
    }

    public function testNothingHappenIfNoCredentialsRetrieved(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->once();

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment can't be added on commit #azer12563 because there is no bot API token.")
            ->once();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturnNull()
            ->once();

        $this->client_wrapper->shouldReceive('postUrl')->never();

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            [new WebhookTuleapReference(123)]
        );
    }

    public function testClientWrapperThrowErrorAndLogIt(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->twice();

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturn($credentials)
            ->once();

        $references = [
            new WebhookTuleapReference(123),
            new WebhookTuleapReference(59)
        ];

        $references_presenter = [
            new BotCommentReferencePresenter(123, "https://example.fr"),
            new BotCommentReferencePresenter(59, "https://example.fr")
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/repository/commits/azer12563/comments";
        $comment = "\nThis commit references:\n * [TULEAP-123](https://example.fr)\n * [TULEAP-59](https://example.fr)\n";

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ["note" => $comment])
            ->once()
            ->andThrow(new GitlabRequestException(404, "not found"));

        $this->logger
            ->shouldReceive('error')
            ->with("An error occurred during automatically comment commit #azer12563")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("|  |_Error returned by the GitLab server: not found")
            ->once();

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }

    public function testPOSTCommentOnCommit(): void
    {
        $this->webhook_data
            ->shouldReceive("getSha1")
            ->andReturn("azer12563")
            ->twice();

        $this->gitlab_repository
            ->shouldReceive('getGitlabRepositoryId')
            ->andReturn(4)
            ->once();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($this->gitlab_repository)
            ->andReturn($credentials)
            ->once();

        $references = [
            new WebhookTuleapReference(123),
        ];

        $references_presenter = [
            new BotCommentReferencePresenter(123, "https://example.fr"),
        ];

        $this->bot_comment_reference_presenter_builder
            ->shouldReceive('build')
            ->with($references)
            ->andReturn($references_presenter)
            ->once();

        $url     = "/projects/4/repository/commits/azer12563/comments";
        $comment = "This commit references: [TULEAP-123](https://example.fr).\n";

        $this->client_wrapper
            ->shouldReceive('postUrl')
            ->with($credentials, $url, ["note" => $comment])
            ->once();

        $this->logger
            ->shouldReceive("debug")
            ->with("Comment was successfully added on commit #azer12563")
            ->once();

        $this->commenter->addCommentOnCommit(
            $this->webhook_data,
            $this->gitlab_repository,
            $references
        );
    }

    public function testItDoesNotAddArtifactCommentIfAnErrorOccursDuringTheCommentCreation(): void
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
                "email"     => "L.Asticot.Coco@email.example.fr",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andThrow(Tracker_NoChangeException::class);
        $this->logger->shouldReceive("error")->with("An error occurred during the creation of the comment")->once();

        $this->commenter->addTuleapArtifactComment($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItDoesNotAddArtifactCommentIfTheCommentIsNotCreated(): void
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
                "email"     => "L.Asticot.Coco@email.example.fr",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturnNull();
        $this->logger->shouldReceive("error")->with("No new comment was created")->once();

        $this->commenter->addTuleapArtifactComment($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithTheTuleapUsernameIfTheTuleapUserExists(): void
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
                "email"     => "L.Asticot.Coco@email.example.fr",
                "user_name" => "asticotc",
                'language_id' => 'en'
            ]
        );
        $this->user_manager->shouldReceive("getUserByEmail")->with($committer_email)->andReturn($committer);

        $message = "@asticotc attempts to close this artifact from GitLab but no status semantic defined.";
        $artifact->shouldReceive("createNewChangeset")->with([], $message, $tracker_workflow_user)->andReturn(Mockery::mock(Tracker_Artifact_Changeset::class));

        $this->logger->shouldNotReceive("error");

        $this->commenter->addTuleapArtifactComment($artifact, $tracker_workflow_user, $this->webhook_data);
    }

    public function testItCreatesANewCommentWithTheGitlabCommitterAuthorIfTheTuleapUserDoesNotExist(): void
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

        $this->logger->shouldNotReceive("error");

        $this->commenter->addTuleapArtifactComment($artifact, $tracker_workflow_user, $this->webhook_data);
    }
}
