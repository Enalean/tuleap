<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Bugzilla;

use CrossReference;
use Http\Mock\Client;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Bugzilla\Reference\Reference;
use Tuleap\Bugzilla\Reference\RESTReferenceCreator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;

final class RESTReferenceCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testReferenceIsCommunicatedToTheBugzillaServer(): void
    {
        $http_client = new Client();
        $logger      = Mockery::mock(LoggerInterface::class);
        $creator     = new RESTReferenceCreator(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger
        );

        $logger->shouldReceive('info');
        $logger->shouldNotReceive('error');

        $cross_reference    = new CrossReference(
            '1000',
            '101',
            'bz',
            'source_keyword',
            '2000',
            '101',
            'bz',
            'target_keyword',
            '102'
        );
        $rest_url           = 'https://bz.example.com/api';
        $bugzilla_reference = new Reference(
            1,
            'bz',
            'https://example.com',
            'user1',
            new ConcealedString('api_key'),
            true,
            $rest_url,
            true
        );

        $creator->create($cross_reference, $bugzilla_reference);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $executed_request = $requests[0];
        $this->assertEquals('POST', $executed_request->getMethod());
        $this->assertStringStartsWith($rest_url, (string) $executed_request->getUri());
    }
}
