<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class JenkinsCSRFCrumbRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRetrievalCSRFHeader()
    {
        $csrf_crumb_header = 'Jenkins-Crumb:eb62a7b696ef05275be811c0608cbbcb';
        $http_client       = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('addOptions')->with([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => 'https://example.com/crumbIssuer/api/xml?xpath=concat%28%2F%2FcrumbRequestField%2C%22%3A%22%2C%2F%2Fcrumb%29'
        ]);
        $http_client->shouldReceive('doRequest');
        $http_client->shouldReceive('getLastResponse')->andReturns($csrf_crumb_header);

        $csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client);

        $test_urls = ['https://example.com', 'https://example.com/'];
        foreach ($test_urls as $test_url) {
            $this->assertEquals(
                $csrf_crumb_retriever->getCSRFCrumbHeader($test_url),
                $csrf_crumb_header
            );
        }
    }

    public function testItDoesNotFailOnHTTPError()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest')->andThrows(\Http_ClientException::class);

        $csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client);

        $this->assertEquals(
            $csrf_crumb_retriever->getCSRFCrumbHeader('https://example.com'),
            ''
        );
    }
}
