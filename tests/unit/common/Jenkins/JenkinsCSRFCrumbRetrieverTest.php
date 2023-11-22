<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Jenkins;

use Exception;
use Http\Mock\Client;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Tuleap\Http\HTTPFactoryBuilder;

class JenkinsCSRFCrumbRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @testWith ["https://example.com"]
     *           ["https://example.com/"]
     */
    public function testRetrievalCSRFHeader(string $test_url): void
    {
        $csrf_crumb_header = 'Jenkins-Crumb:eb62a7b696ef05275be811c0608cbbcb';
        $http_client       = new Client();
        $http_client->addResponse(
            HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream($csrf_crumb_header)
            )
        );

        $csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client, HTTPFactoryBuilder::requestFactory());

        self::assertEquals(
            $csrf_crumb_retriever->getCSRFCrumbHeader($test_url),
            $csrf_crumb_header
        );
        $requests = $http_client->getRequests();
        self::assertCount(1, $requests);
        $expected_request_url = 'https://example.com/crumbIssuer/api/xml?xpath=concat%28%2F%2FcrumbRequestField%2C%22%3A%22%2C%2F%2Fcrumb%29';
        self::assertEquals($expected_request_url, (string) $requests[0]->getUri());
    }

    public function testItDoesNotFailOnHTTPError(): void
    {
        $http_client = new Client();
        $http_client->addResponse(
            HTTPFactoryBuilder::responseFactory()->createResponse(500)
        );

        $csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client, HTTPFactoryBuilder::requestFactory());

        self::assertEquals(
            $csrf_crumb_retriever->getCSRFCrumbHeader('https://example.com'),
            ''
        );
    }

    public function testItDoesNotFailOnNetworkError(): void
    {
        $http_client = $this->createMock(ClientInterface::class);
        $http_client->method('sendRequest')->willThrowException(
            new class extends Exception implements ClientExceptionInterface {
            }
        );

        $csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client, HTTPFactoryBuilder::requestFactory());

        self::assertEquals(
            $csrf_crumb_retriever->getCSRFCrumbHeader('https://example.com'),
            ''
        );
    }
}
