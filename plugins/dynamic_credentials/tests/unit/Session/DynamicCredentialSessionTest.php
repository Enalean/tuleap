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

namespace Tuleap\DynamicCredentials\Session;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialAuthenticationException;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;

final class DynamicCredentialSessionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSessionIsStartedWhenAuthenticationIsSuccessful(): void
    {
        $credential_retriever = $this->createMock(CredentialRetriever::class);
        $credential           = $this->createMock(Credential::class);
        $credential->method('getIdentifier')->willReturn('identifier');
        $credential_retriever->expects(self::once())->method('authenticate')->willReturn($credential);
        $credential_retriever->expects(self::once())->method('getByIdentifier')->willReturn($credential);
        $storage = new DynamicCredentialNonPersistentStorage();

        $dynamic_session = new DynamicCredentialSession($storage, $credential_retriever);

        $dynamic_session->initialize('username', new ConcealedString('password'));

        self::assertSame('identifier', $storage->getIdentifier());
        self::assertSame($credential, $dynamic_session->getAssociatedCredential());
    }

    public function testCredentialIsNotRetrievedWhenSessionIsNotInitialized(): void
    {
        $credential_retriever = $this->createMock(CredentialRetriever::class);

        $dynamic_session = new DynamicCredentialSession(new DynamicCredentialNonPersistentStorage(), $credential_retriever);

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }

    public function testSessionIsNotInitializedWhenAuthenticationFail(): void
    {
        $credential_retriever = $this->createMock(CredentialRetriever::class);
        $credential_retriever->expects(self::once())->method('authenticate')->willThrowException(new CredentialAuthenticationException());

        $dynamic_session = new DynamicCredentialSession(new DynamicCredentialNonPersistentStorage(), $credential_retriever);

        try {
            $dynamic_session->initialize('username', new ConcealedString('password'));
        } catch (CredentialAuthenticationException $ex) {
        }

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }
}
