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

namespace Tuleap\DynamicCredentials\Session;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialAuthenticationException;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;

class DynamicCredentialSessionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSessionIsStartedWhenAuthenticationIsSuccessful(): void
    {
        $credential_retriever = Mockery::mock(CredentialRetriever::class);
        $credential           = Mockery::mock(Credential::class);
        $credential->shouldReceive('getIdentifier')->andReturn('identifier');
        $credential_retriever->shouldReceive('authenticate')->once()->andReturn($credential);
        $credential_retriever->shouldReceive('getByIdentifier')->once()->andReturn($credential);
        $storage = new DynamicCredentialNonPersistentStorage();

        $dynamic_session = new DynamicCredentialSession($storage, $credential_retriever);

        $dynamic_session->initialize('username', new ConcealedString('password'));

        $this->assertSame('identifier', $storage->getIdentifier());
        $this->assertSame($credential, $dynamic_session->getAssociatedCredential());
    }

    public function testCredentialIsNotRetrievedWhenSessionIsNotInitialized()
    {
        $credential_retriever = Mockery::mock(CredentialRetriever::class);

        $dynamic_session = new DynamicCredentialSession(new DynamicCredentialNonPersistentStorage(), $credential_retriever);

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }

    public function testSessionIsNotInitializedWhenAuthenticationFail()
    {
        $credential_retriever = Mockery::mock(CredentialRetriever::class);
        $credential_retriever->shouldReceive('authenticate')->once()->andThrow(CredentialAuthenticationException::class);

        $dynamic_session = new DynamicCredentialSession(new DynamicCredentialNonPersistentStorage(), $credential_retriever);

        try {
            $dynamic_session->initialize('username', new ConcealedString('password'));
        } catch (CredentialAuthenticationException $ex) {
        }

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }
}
