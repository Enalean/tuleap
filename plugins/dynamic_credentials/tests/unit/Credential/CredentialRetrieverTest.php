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

namespace Tuleap\DynamicCredentials\Credential;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class CredentialRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAuthenticateCredential()
    {
        $expiration_date = new \DateTimeImmutable('+10 minutes');
        $dao             = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('verifyHashPassword')->andReturn(true);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract')->andReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $credential = $credential_retriever->authenticate('username', new ConcealedString('password'));

        $this->assertEquals('identifier', $credential->getIdentifier());
    }

    public function testAuthenticationRejectsWronglyFormattedUsername()
    {
        $dao                  = Mockery::mock(CredentialDAO::class);
        $password_handler     = Mockery::mock(\PasswordHandler::class);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract')->andThrow(CredentialInvalidUsernameException::class);

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialInvalidUsernameException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsUnknownCredential()
    {
        $dao = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn([]);
        $password_handler     = Mockery::mock(\PasswordHandler::class);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract')->andReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialNotFoundException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsInvalidPassword()
    {
        $expiration_date = new \DateTimeImmutable('+10 minutes');
        $dao             = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('verifyHashPassword')->andReturn(false);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract')->andReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialAuthenticationException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsExpiredAccount()
    {
        $expiration_date = new \DateTimeImmutable('-10 minutes');
        $dao             = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('verifyHashPassword')->andReturn(true);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract')->andReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialExpiredException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testCredentialCanBeRetrievedByIdentifier()
    {
        $dao = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => 0]
        );
        $password_handler     = Mockery::mock(\PasswordHandler::class);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $credential = $credential_retriever->getByIdentifier('identifier');

        $this->assertEquals('identifier', $credential->getIdentifier());
    }

    public function testExceptionIsThrownWhenCredentialIdentifierDoesNotExist()
    {
        $dao             = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('getUnrevokedCredentialByIdentifier')->andReturn([]);
        $password_handler     = Mockery::mock(\PasswordHandler::class);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialNotFoundException::class);

        $credential_retriever->getByIdentifier('identifier');
    }
}
