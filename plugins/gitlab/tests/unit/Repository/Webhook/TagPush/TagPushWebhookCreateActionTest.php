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

use DateTimeImmutable;
use Psr\Log\NullLogger;
use Reference;
use ReferenceManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\Tag\GitlabTag;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Reference\CrossReference;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class TagPushWebhookCreateActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabTagRetriever
     */
    private $gitlab_tag_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TagInfoDao
     */
    private $tag_info_dao;

    private WebhookTuleapReferencesParser $tuleap_references_parser;
    private TagPushWebhookCreateAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials_retriever      = $this->createMock(CredentialsRetriever::class);
        $this->gitlab_tag_retriever       = $this->createMock(GitlabTagRetriever::class);
        $this->tuleap_references_parser   = new WebhookTuleapReferencesParser();
        $this->tuleap_reference_retriever = $this->createMock(TuleapReferenceRetriever::class);
        $this->reference_manager          = $this->createMock(ReferenceManager::class);
        $this->tag_info_dao               = $this->createMock(TagInfoDao::class);

        $this->action = new TagPushWebhookCreateAction(
            $this->credentials_retriever,
            $this->gitlab_tag_retriever,
            $this->tuleap_references_parser,
            $this->tuleap_reference_retriever,
            $this->reference_manager,
            $this->tag_info_dao,
            new NullLogger()
        );
    }

    public function testItSavesTheTagReference(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "after",
        );

        $credentials = new Credentials(
            "https://example.com",
            IntegrationApiToken::buildBrandNewToken(new ConcealedString("DAT-TOKEN"))
        );

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $gitlab_tag = new GitlabTag(
            "v1.0.2",
            "This tag references TULEAP-2337",
            "sha1"
        );

        $this->gitlab_tag_retriever
            ->expects(self::once())
            ->method('getTagFromGitlabAPI')
            ->with($credentials, $integration, "v1.0.2")
            ->willReturn($gitlab_tag);

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

        $this->tuleap_reference_retriever->method('retrieveTuleapReference')
            ->with(2337)
            ->willReturn($tuleap_reference);

        $this->reference_manager
            ->expects(self::once())
            ->method('insertCrossReference')
            ->with(
                $this->callback(
                    function (CrossReference $cross_reference): bool {
                        return $cross_reference->getRefSourceType() === GitlabTagReference::NATURE_NAME
                            && $cross_reference->getRefSourceKey() === GitlabTagReference::REFERENCE_NAME;
                    }
                )
            );

        $this->tag_info_dao
            ->expects(self::once())
            ->method('saveGitlabTagInfo');

        $this->action->createTagReferences(
            $integration,
            $tag_webhook_data
        );
    }

    public function testItDoesNothingIfThereIsNoCrendentialForRepository(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "after",
        );

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn(null);

        $this->reference_manager->expects(self::never())->method('insertCrossReference');
        $this->tag_info_dao->expects(self::never())->method('saveGitlabTagInfo');

        $this->action->createTagReferences(
            $integration,
            $tag_webhook_data
        );
    }

    public function testItDoesNothingIfThereIsNoReferenceInTagMessage(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "after",
        );

        $credentials = new Credentials(
            "https://example.com",
            IntegrationApiToken::buildBrandNewToken(new ConcealedString("DAT-TOKEN"))
        );

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $gitlab_tag = new GitlabTag(
            "v1.0.2",
            "This tag references 2337",
            "sha1"
        );

        $this->gitlab_tag_retriever
            ->expects(self::once())
            ->method('getTagFromGitlabAPI')
            ->with($credentials, $integration, "v1.0.2")
            ->willReturn($gitlab_tag);

        $this->reference_manager->expects(self::never())->method('insertCrossReference');
        $this->tag_info_dao->expects(self::never())->method('saveGitlabTagInfo');

        $this->action->createTagReferences(
            $integration,
            $tag_webhook_data
        );
    }

    public function testItDoesNothingIfThereIsNoValidReferencesInTagMessage(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "after",
        );

        $credentials = new Credentials(
            "https://example.com",
            IntegrationApiToken::buildBrandNewToken(new ConcealedString("DAT-TOKEN"))
        );

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $gitlab_tag = new GitlabTag(
            "v1.0.2",
            "This tag references tuleap-1337",
            "sha1"
        );

        $this->gitlab_tag_retriever
            ->expects(self::once())
            ->method('getTagFromGitlabAPI')
            ->with($credentials, $integration, "v1.0.2")
            ->willReturn($gitlab_tag);

        $this->tuleap_reference_retriever
            ->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(1337)
            ->willThrowException(
                new TuleapReferenceNotFoundException()
            );

        $this->reference_manager->expects(self::never())->method('insertCrossReference');
        $this->tag_info_dao->expects(self::never())->method('saveGitlabTagInfo');

        $this->action->createTagReferences(
            $integration,
            $tag_webhook_data
        );
    }
}
