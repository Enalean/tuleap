<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookData;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookDataBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookData;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookDataBuilder;
use Psr\Log\NullLogger;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WebhookDataExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    private WebhookDataExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $post_push_webhook_data_builder = new PostPushWebhookDataBuilder(
            new PostPushCommitWebhookDataExtractor(
                new NullLogger()
            )
        );

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->extractor = new WebhookDataExtractor(
            $post_push_webhook_data_builder,
            new PostMergeRequestWebhookDataBuilder(new \Psr\Log\NullLogger()),
            new TagPushWebhookDataBuilder(),
            $this->logger
        );
    }

    public function testItThrowsAnExceptionIfEventHeaderIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream('{}')
        );

        $this->expectException(MissingEventHeaderException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfEventIsNotAKnownEvent(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Whatever Hook'
        );

        $this->expectException(EventNotAllowedException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectKeyIsMissingInEvent(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Push Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectIdKeyIsMissingInPostPush(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{}}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Push Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectIdKeyIsMissingInPostMergeRequest(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{}}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Merge Request Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectIdKeyIsMissingInTagPushRequest(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{}}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Tag Push Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectHttpURLKeyIsMissingInPostPush(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456}, "commits": []}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Push Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectHttpURLKeyIsMissingInPostMergeRequest(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456}, "commits": []}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Merge Request Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectHttpURLKeyIsMissingInTagPushRequest(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456}, "commits": []}'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Tag Push Hook'
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItRetrievesPostPushWebhookData(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('|_ Webhook of type Push Hook received.');

        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456, "web_url": "https://example.com/path/repo01"},
                  "ref": "refs/heads/master",
                  "checkout_sha": "08596fb6360bcc951a06471c616f8bc77800d4f4",
                  "after": "whatever_after",
                  "commits": [
                      {
                          "id": "feff4ced04b237abb8b4a50b4160099313152c3c",
                          "title": "commit 01",
                          "message": "commit 01",
                          "timestamp": "2020-12-16T10:21:50+01:00",
                          "author": {
                            "name": "John Snow",
                            "email": "john-snow@example.com"
                          }
                      },
                      {
                          "id": "08596fb6360bcc951a06471c616f8bc77800d4f4",
                          "title": "commit 02",
                          "message": "commit 02",
                          "timestamp": "2020-12-16T10:21:52+01:00",
                          "author": {
                            "name": "John Snow",
                            "email": "john-snow@example.com"
                          }
                      }
                  ]
                }'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Push Hook'
        );

        $webhook_data = $this->extractor->retrieveWebhookData(
            $request
        );

        self::assertSame('Push Hook', $webhook_data->getEventName());
        self::assertSame(123456, $webhook_data->getGitlabProjectId());
        self::assertSame('https://example.com/path/repo01', $webhook_data->getGitlabWebUrl());
        self::assertInstanceOf(PostPushWebhookData::class, $webhook_data);
        self::assertCount(2, $webhook_data->getCommits());
    }

    public function testItRetrievesPostMergeRequestWebhookData(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('|_ Webhook of type Merge Request Hook received.');

        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456, "web_url": "https://example.com/path/repo01"},
                  "object_attributes":{
                    "iid": 2,
                    "title": "My Title",
                    "description": "My Description",
                    "state": "opened",
                    "created_at": "2021-01-12 13:49:35 UTC",
                    "author_id": 10,
                    "source_branch": "some_feature"
                  }
                }'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Merge Request Hook'
        );

        $webhook_data = $this->extractor->retrieveWebhookData(
            $request
        );

        self::assertSame('Merge Request Hook', $webhook_data->getEventName());
        self::assertSame(123456, $webhook_data->getGitlabProjectId());
        self::assertSame('https://example.com/path/repo01', $webhook_data->getGitlabWebUrl());
        self::assertInstanceOf(PostMergeRequestWebhookData::class, $webhook_data);
        self::assertSame(2, $webhook_data->getMergeRequestId());
        self::assertSame('My Title', $webhook_data->getTitle());
        self::assertSame('My Description', $webhook_data->getDescription());
    }

    public function testItRetrievesTagPushRequestWebhookData(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('|_ Webhook of type Tag Push Hook received.');

        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"project":{"id": 123456, "web_url": "https://example.com/path/repo01"},
                  "ref": "refs/tags/v1.0",
                  "before": "before",
                  "after": "after"
                }'
            )
        )->withHeader(
            'X-Gitlab-Event',
            'Tag Push Hook'
        );

        $webhook_data = $this->extractor->retrieveWebhookData(
            $request
        );

        self::assertSame('Tag Push Hook', $webhook_data->getEventName());
        self::assertSame(123456, $webhook_data->getGitlabProjectId());
        self::assertSame('https://example.com/path/repo01', $webhook_data->getGitlabWebUrl());
        self::assertInstanceOf(TagPushWebhookData::class, $webhook_data);
        self::assertSame('refs/tags/v1.0', $webhook_data->getRef());
    }
}
