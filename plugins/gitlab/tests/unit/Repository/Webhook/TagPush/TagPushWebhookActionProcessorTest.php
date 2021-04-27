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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use CrossReference;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\NullLogger;
use Reference;
use ReferenceManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\Tag\GitlabTag;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiToken;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class TagPushWebhookActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TagPushWebhookActionProcessor
     */
    private $action_processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabTagRetriever
     */
    private $gitlab_tag_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var WebhookTuleapReferencesParser
     */
    private $tuleap_references_parser;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TagInfoDao
     */
    private $tag_info_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials_retriever               = Mockery::mock(CredentialsRetriever::class);
        $this->gitlab_tag_retriever                = Mockery::mock(GitlabTagRetriever::class);
        $this->tuleap_references_parser            = new WebhookTuleapReferencesParser();
        $this->tuleap_reference_retriever          = Mockery::mock(TuleapReferenceRetriever::class);
        $this->gitlab_repository_project_retriever = Mockery::mock(GitlabRepositoryProjectRetriever::class);
        $this->reference_manager                   = Mockery::mock(ReferenceManager::class);
        $this->tag_info_dao                        = Mockery::mock(TagInfoDao::class);

        $this->action_processor = new TagPushWebhookActionProcessor(
            $this->credentials_retriever,
            $this->gitlab_tag_retriever,
            $this->tuleap_references_parser,
            $this->tuleap_reference_retriever,
            $this->gitlab_repository_project_retriever,
            $this->reference_manager,
            $this->tag_info_dao,
            new NullLogger()
        );
    }

    public function testItSavesTheTagReference(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable()
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
        );

        $credentials = new Credentials(
            "https://example.com",
            GitlabBotApiToken::buildBrandNewToken(new ConcealedString("DAT-TOKEN"))
        );

        $this->credentials_retriever->shouldReceive('getCredentials')
            ->once()
            ->with($gitlab_repository)
            ->andReturn($credentials);

        $gitlab_tag = new GitlabTag(
            "v1.0.2",
            "This tag references TULEAP-2337",
            "sha1"
        );

        $this->gitlab_tag_retriever->shouldReceive('getTagFromGitlabAPI')
            ->once()
            ->with($credentials, $gitlab_repository, "v1.0.2")
            ->andReturn($gitlab_tag);

        $project = Project::buildForTest();
        $this->gitlab_repository_project_retriever->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->once()
            ->with($gitlab_repository)
            ->andReturn([$project]);

        $tuleap_reference = new Reference(
            2337,
            'art',
            '',
            'https://example.com',
            'P',
            'plugin_tracker',
            'plugin_tracker_artifact',
            true,
            101
        );

        $this->tuleap_reference_retriever->shouldReceive('retrieveTuleapReference')
            ->with(2337)
            ->andReturn($tuleap_reference);

        $this->reference_manager->shouldReceive('insertCrossReference')
            ->once()
            ->with(
                Mockery::on(
                    function (CrossReference $cross_reference): bool {
                        return $cross_reference->getRefSourceType() === GitlabTagReference::NATURE_NAME
                            && $cross_reference->getRefSourceKey() === GitlabTagReference::REFERENCE_NAME;
                    }
                )
            );

        $this->tag_info_dao->shouldReceive('saveGitlabTagInfo')
            ->once();

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }

    public function testItDoesNothingIfThereIsNoCrendentialForRepository(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable()
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
        );

        $this->credentials_retriever->shouldReceive('getCredentials')
            ->once()
            ->with($gitlab_repository)
            ->andReturnNull();

        $this->reference_manager->shouldNotReceive('insertCrossReference');
        $this->tag_info_dao->shouldNotReceive('saveGitlabTagInfo');

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }

    public function testItDoesNothingIfThereIsNoReferenceInTagMessage(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable()
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
        );

        $credentials = new Credentials(
            "https://example.com",
            GitlabBotApiToken::buildBrandNewToken(new ConcealedString("DAT-TOKEN"))
        );

        $this->credentials_retriever->shouldReceive('getCredentials')
            ->once()
            ->with($gitlab_repository)
            ->andReturn($credentials);

        $gitlab_tag = new GitlabTag(
            "v1.0.2",
            "This tag references 2337",
            "sha1"
        );

        $this->gitlab_tag_retriever->shouldReceive('getTagFromGitlabAPI')
            ->once()
            ->with($credentials, $gitlab_repository, "v1.0.2")
            ->andReturn($gitlab_tag);

        $project = Project::buildForTest();
        $this->gitlab_repository_project_retriever->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->once()
            ->with($gitlab_repository)
            ->andReturn([$project]);

        $this->reference_manager->shouldNotReceive('insertCrossReference');
        $this->tag_info_dao->shouldNotReceive('saveGitlabTagInfo');

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }
}
