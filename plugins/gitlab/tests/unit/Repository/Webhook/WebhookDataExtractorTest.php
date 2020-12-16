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

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;

class WebhookDataExtractorTest extends TestCase
{
    /**
     * @var WebhookDataExtractor
     */
    private $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->post_push_commit_webhook_data_extractor = new PostPushCommitWebhookDataExtractor(
            new NullLogger()
        );

        $this->extractor = new WebhookDataExtractor(
            $this->post_push_commit_webhook_data_extractor
        );
    }

    public function testItThrowsAnExceptionIfEventKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream('{}')
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfEventIsNotAPush(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "whatever"}'
            )
        );

        $this->expectException(EventNotAllowedException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push"}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectIdKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{}}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectHttpURLKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{"id": 123456}, "commits": []}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->extractor->retrieveWebhookData(
            $request
        );
    }

    public function testItRetrievesPostPushWebhookData(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push",
                  "project":{"id": 123456, "web_url": "https://example.com/path/repo01"},
                  "commits": [
                      {
                          "id": "feff4ced04b237abb8b4a50b4160099313152c3c",
                          "title": "commit 01",
                          "message": "commit 01",
                          "timestamp": "2020-12-16T10:21:50+01:00",
                          "author": {
                            "name": "John Snow",
                            "email": "john-snow@the-wall.com"
                          }
                      },
                      {
                          "id": "08596fb6360bcc951a06471c616f8bc77800d4f4",
                          "title": "commit 02",
                          "message": "commit 02",
                          "timestamp": "2020-12-16T10:21:50+01:00",
                          "author": {
                            "name": "John Snow",
                            "email": "john-snow@the-wall.com"
                          }
                      }
                  ]
                }'
            )
        );

        $webhook_data = $this->extractor->retrieveWebhookData(
            $request
        );

        $this->assertSame("push", $webhook_data->getEventName());
        $this->assertSame(123456, $webhook_data->getGitlabProjectId());
        $this->assertSame("https://example.com/path/repo01", $webhook_data->getGitlabWebUrl());
        $this->assertCount(2, $webhook_data->getCommits());
    }
}
