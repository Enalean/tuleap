<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\RealtimeMercure;

use Http\Mock\Client;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\JWT\generators\MercureJWTGeneratorImpl;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\PHPUnit\TestCase;

final class RealTimeMercureArtifactMessageSenderTest extends TestCase
{
    public function testNoError(): void
    {
        $jwt_configuration               = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText(str_repeat('a', 32)));
        $mercure_jwt_generator           = new MercureJWTGeneratorImpl($jwt_configuration);
        $data                            = "data";
        $topic                           = "topic";
        $logger                          = new TestLogger();
        $http_client                     = new Client();
        $mercure_client                  = new MercureClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger,
            $mercure_jwt_generator
        );
        $artifact_message_sender_mercure = new RealTimeMercureArtifactMessageSender($mercure_client);

        $artifact_message_sender_mercure->sendMessage($data, $topic);
        $request       = $http_client->getRequests()[0];
        $request_table = [
            'data' => json_encode($data),
            'topic' => $topic,
            'private' => 'on',
        ];
        $this->assertEquals(http_build_query($request_table), $request->getBody()->getContents());
    }
}
