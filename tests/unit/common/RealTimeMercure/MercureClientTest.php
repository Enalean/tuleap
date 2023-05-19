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
namespace Tuleap\RealTimeMercure;

use Exception;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use ColinODell\PsrTestLogger\TestLogger;
use Psr\Http\Client\ClientExceptionInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\JWT\generators\MercureJWTGeneratorImpl;
use Tuleap\JWT\generators\NullMercureJWTGenerator;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

final class MercureClientTest extends TestCase
{
    use GlobalLanguageMock;

    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */

    /** @var  MercureJWTGeneratorImpl */
    private $mercure_jwt_generator;

    /**
     * @var Configuration
     */
    private $jwt_configuration;

    protected function setup(): void
    {
        parent::setup();
        $user = new \PFUser();

        $this->user_manager = $this->createStub(UserManager::class);
        $this->user_manager->method('getCurrentUser')->willReturn($user);


        $this->jwt_configuration     = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText(str_repeat('a', 32)));
        $this->mercure_jwt_generator = new MercureJWTGeneratorImpl($this->jwt_configuration);
    }

    public function testMessageIsTransmittedToRealTimeMercureServer(): void
    {
        $http_client    = new \Http\Mock\Client();
        $logger         = new TestLogger();
        $mercure_client = new MercureClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger,
            $this->mercure_jwt_generator
        );

        $mercure_client->sendMessage(
            new MercureMessageDataPresenter(
                'test/test',
                'test_test_test_test_test'
            )
        );
        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $this->assertFalse($logger->hasError('Not able to send a message to the Mercure server'));
        $this->assertFalse($logger->hasError('Mercure server has not processed a message'));
        $request = $requests[0];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringStartsWith('http://localhost:3000/.well-known/mercure', (string) $request->getUri());
    }

    public function testMessageCantBeSent(): void
    {
        $http_client    = new \Http\Mock\Client();
        $logger         = new TestLogger();
        $mercure_client = new MercureClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger,
            $this->mercure_jwt_generator
        );
        $exception      = new class extends Exception implements ClientExceptionInterface{
        };
        $http_client->addException($exception);
        $mercure_client->sendMessage(
            new MercureMessageDataPresenter(
                'test/test',
                'test_test_test_test_test'
            )
        );
        $this->assertTrue($logger->hasError('Not able to send a message to the Mercure server'));
    }

    public function testMessageMercureServerProcessingFailed(): void
    {
        $http_client    = new \Http\Mock\Client();
        $logger         = new TestLogger();
        $mercure_client = new MercureClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger,
            $this->mercure_jwt_generator
        );
        $http_response  = HTTPFactoryBuilder::responseFactory()->createResponse(403);
        $http_client->addResponse($http_response);
        $mercure_client->sendMessage(
            new MercureMessageDataPresenter(
                'test/test',
                'test_test_test_test_test'
            )
        );
        $this->assertTrue($logger->hasError(sprintf('Mercure server has not processed a message: %d %s', $http_response->getStatusCode(), $http_response->getReasonPhrase())));
    }

    public function testNoMercureGenerator(): void
    {
        $http_client           = new \Http\Mock\Client();
        $logger                = new TestLogger();
        $mercure_jwt_generator = new NullMercureJWTGenerator();
        $mercure_client        = new MercureClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger,
            $mercure_jwt_generator
        );
        $http_response         = HTTPFactoryBuilder::responseFactory()->createResponse(403);
        $http_client->addResponse($http_response);
        $mercure_client->sendMessage(
            new MercureMessageDataPresenter(
                'test/test',
                'test_test_test_test_test'
            )
        );
        $this->assertTrue($logger->hasError('Error while generating mercure authentication token generation'));
    }
}
