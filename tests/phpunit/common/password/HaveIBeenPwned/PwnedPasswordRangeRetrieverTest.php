<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\password\HaveIBeenPwned;

use PHPUnit\Framework\TestCase;

class PwnedPasswordRangeRetrieverTest extends TestCase
{
    public function testAPIResponseIsRetrieved()
    {
        $http_client = $this->createMock(\Http_Client::class);
        $http_client->method('getLastResponse')->willReturn('API_RESPONSE');
        $logger = $this->createMock(\Logger::class);

        $retriever     = new PwnedPasswordRangeRetriever($http_client, $logger);
        $hash_suffixes = $retriever->getHashSuffixesMatchingPrefix('AAAAA');

        $this->assertEquals('API_RESPONSE', $hash_suffixes);
    }

    /**
     * @expectedException \LengthException
     */
    public function testTooLongPrefixIsRejected()
    {
        $http_client = $this->createMock(\Http_Client::class);
        $logger      = $this->createMock(\Logger::class);

        $retriever = new PwnedPasswordRangeRetriever($http_client, $logger);
        $retriever->getHashSuffixesMatchingPrefix(sha1('password'));
    }

    public function testAPICallErrorGivesEmptyResponse()
    {
        $http_client = $this->createMock(\Http_Client::class);
        $http_client->method('doRequest')->willThrowException(new \Http_ClientException());
        $logger = $this->createMock(\Logger::class);
        $logger->expects($this->once())->method('info');

        $retriever     = new PwnedPasswordRangeRetriever($http_client, $logger);
        $hash_suffixes = $retriever->getHashSuffixesMatchingPrefix('AAAAA');

        $this->assertEquals('', $hash_suffixes);
    }
}
