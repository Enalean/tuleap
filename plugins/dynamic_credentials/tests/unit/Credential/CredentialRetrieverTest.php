<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Credential;

use Tuleap\Cryptography\ConcealedString;

final class CredentialRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAuthenticateCredential(): void
    {
        $expiration_date = new \DateTimeImmutable('+10 minutes');
        $dao             = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = $this->createMock(\PasswordHandler::class);
        $password_handler->method('verifyHashPassword')->willReturn(true);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract')->willReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $credential = $credential_retriever->authenticate('username', new ConcealedString('password'));

        self::assertEquals('identifier', $credential->getIdentifier());
    }

    public function testAuthenticationRejectsWronglyFormattedUsername(): void
    {
        $dao                  = $this->createMock(CredentialDAO::class);
        $password_handler     = $this->createMock(\PasswordHandler::class);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract')->willThrowException(new CredentialInvalidUsernameException());

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialInvalidUsernameException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsUnknownCredential(): void
    {
        $dao = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn([]);
        $password_handler     = $this->createMock(\PasswordHandler::class);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract')->willReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialNotFoundException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsInvalidPassword(): void
    {
        $expiration_date = new \DateTimeImmutable('+10 minutes');
        $dao             = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = $this->createMock(\PasswordHandler::class);
        $password_handler->method('verifyHashPassword')->willReturn(false);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract')->willReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialAuthenticationException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testAuthenticationRejectsExpiredAccount(): void
    {
        $expiration_date = new \DateTimeImmutable('-10 minutes');
        $dao             = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => $expiration_date->getTimestamp()]
        );
        $password_handler = $this->createMock(\PasswordHandler::class);
        $password_handler->method('verifyHashPassword')->willReturn(true);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract')->willReturn('identifier');

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialExpiredException::class);

        $credential_retriever->authenticate('username', new ConcealedString('password'));
    }

    public function testCredentialCanBeRetrievedByIdentifier(): void
    {
        $dao = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn(
            ['identifier' => 'identifier', 'password' => 'password', 'expiration' => 0]
        );
        $password_handler     = $this->createMock(\PasswordHandler::class);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $credential = $credential_retriever->getByIdentifier('identifier');

        self::assertEquals('identifier', $credential->getIdentifier());
    }

    public function testExceptionIsThrownWhenCredentialIdentifierDoesNotExist(): void
    {
        $dao = $this->createMock(CredentialDAO::class);
        $dao->method('getUnrevokedCredentialByIdentifier')->willReturn([]);
        $password_handler     = $this->createMock(\PasswordHandler::class);
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);

        $credential_retriever = new CredentialRetriever($dao, $password_handler, $identifier_extractor);

        $this->expectException(CredentialNotFoundException::class);

        $credential_retriever->getByIdentifier('identifier');
    }
}
