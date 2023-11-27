<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Password\HaveIBeenPwned;

use Http\Client\Exception;
use Http\Mock\Client;
use Psr\Log\NullLogger;
use Tuleap\Http\HTTPFactoryBuilder;

class PwnedPasswordRangeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAPIResponseIsRetrieved(): void
    {
        $http_client   = new Client();
        $response_body = HTTPFactoryBuilder::streamFactory()->createStream('API_RESPONSE');
        $response      = HTTPFactoryBuilder::responseFactory()->createResponse(200)->withBody($response_body);
        $http_client->addResponse($response);

        $retriever     = new PwnedPasswordRangeRetriever(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            new NullLogger()
        );
        $hash_suffixes = $retriever->getHashSuffixesMatchingPrefix('AAAAA');

        self::assertEquals('API_RESPONSE', $hash_suffixes);
    }

    public function testTooLongPrefixIsRejected(): void
    {
        $retriever = new PwnedPasswordRangeRetriever(
            new Client(),
            HTTPFactoryBuilder::requestFactory(),
            new NullLogger()
        );

        self::expectException(\LengthException::class);

        $retriever->getHashSuffixesMatchingPrefix(sha1('password'));
    }

    public function testAPICallWithInvalidResponseGivesEmptyResult(): void
    {
        $http_client = new Client();
        $response    = HTTPFactoryBuilder::responseFactory()->createResponse(504);
        $http_client->addResponse($response);

        $retriever = new PwnedPasswordRangeRetriever(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            new NullLogger()
        );

        $hash_suffixes = $retriever->getHashSuffixesMatchingPrefix('AAAAA');

        self::assertEquals('', $hash_suffixes);
    }

    public function testAPICallErrorGivesEmptyResult(): void
    {
        $http_client = new Client();
        $http_client->addException($this->createMock(Exception\RequestException::class));

        $retriever = new PwnedPasswordRangeRetriever(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            new NullLogger()
        );

        $hash_suffixes = $retriever->getHashSuffixesMatchingPrefix('AAAAA');

        self::assertEquals('', $hash_suffixes);
    }
}
