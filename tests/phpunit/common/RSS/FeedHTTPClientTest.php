<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\RSS;

use Http\Client\Exception;
use Http\Mock\Client;
use Laminas\Feed\Exception\RuntimeException as FeedRuntimeException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class FeedHTTPClientTest extends TestCase
{
    /**
     * @var Client
     */
    private $http_client;
    /**
     * @var FeedHTTPClient
     */
    private $feed_http_client;

    protected function setUp(): void
    {
        $this->http_client = new Client();

        $this->feed_http_client = new FeedHTTPClient($this->http_client, HTTPFactoryBuilder::requestFactory());
    }

    public function testSendHTTPRequest(): void
    {
        $this->http_client->addResponse(
            HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream('My Content')
            )
        );

        $response = $this->feed_http_client->get('https://foo.example.com/rss_feed', ['Bar-Header' => 'Baz']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('My Content', $response->getBody());
        $sent_request = $this->http_client->getLastRequest();
        assert($sent_request instanceof RequestInterface);
        $this->assertEquals('https://foo.example.com/rss_feed', (string) $sent_request->getUri());
        $this->assertEquals('Baz', $sent_request->getHeaderLine('Bar-Header'));
    }

    public function testWrapClientHTTPExceptionInAnExceptionExceptedByTheFeedReader(): void
    {
        $this->http_client->addException(
            new class extends \RuntimeException implements Exception {
            }
        );

        $this->expectException(FeedRuntimeException::class);
        $this->feed_http_client->get('https://foo.example.com/rss_feed_failure');
    }
}
