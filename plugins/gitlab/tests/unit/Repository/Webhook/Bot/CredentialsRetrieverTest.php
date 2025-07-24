<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CredentialsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IntegrationApiTokenRetriever
     */
    private $token_retriever;

    private CredentialsRetriever $credentials_retriever;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->token_retriever = $this->createMock(IntegrationApiTokenRetriever::class);

        $this->credentials_retriever = new CredentialsRetriever(
            $this->token_retriever
        );
    }

    public function testReturnsCredentialsWithTokenAndURL(): void
    {
        $gitlab_repository = $this->createMock(GitlabRepositoryIntegration::class);
        $gitlab_repository
            ->expects($this->once())
            ->method('getGitlabServerUrl')
            ->willReturn('https://www.example.com/');

        $integration_api_token = IntegrationApiToken::buildBrandNewToken(new ConcealedString('My_Token123'));

        $this->token_retriever->method('getIntegrationAPIToken')->with($gitlab_repository)->willReturn($integration_api_token);

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository);

        self::assertNotNull($credentials);
        self::assertEquals($integration_api_token, $credentials->getApiToken());
        self::assertEquals('https://www.example.com/', $credentials->getGitlabServerUrl());
    }

    public function testReturnsNullIfNoSavedToken(): void
    {
        $gitlab_repository = $this->createMock(GitlabRepositoryIntegration::class);
        $gitlab_repository
            ->expects($this->never())
            ->method('getGitlabServerUrl');

        $this->token_retriever->method('getIntegrationAPIToken')->with($gitlab_repository)->willReturn(null);

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository);

        self::assertEquals(null, $credentials);
    }
}
