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

namespace Tuleap\DynamicCredentials\Session;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialAuthenticationException;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;

class DynamicCredentialSessionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSessionIsStartedWhenAuthenticationIsSuccessful(): void
    {
        $credential_retriever = Mockery::mock(CredentialRetriever::class);
        $credential           = Mockery::mock(Credential::class);
        $credential->shouldReceive('getIdentifier')->andReturn('identifier');
        $credential_retriever->shouldReceive('authenticate')->once()->andReturn($credential);
        $credential_retriever->shouldReceive('getByIdentifier')->once()->andReturn($credential);
        $session_storage = [];

        $dynamic_session = new DynamicCredentialSession($session_storage, $credential_retriever);

        $dynamic_session->initialize('username', new ConcealedString('password'));

        $this->assertSame('identifier', current($session_storage));
        $this->assertSame($credential, $dynamic_session->getAssociatedCredential());
    }

    public function testCredentialIsNotRetrievedWhenSessionIsNotInitialized()
    {
        $session_storage      = [];
        $credential_retriever = Mockery::mock(CredentialRetriever::class);

        $dynamic_session = new DynamicCredentialSession($session_storage, $credential_retriever);

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }

    public function testSessionIsNotInitializedWhenAuthenticationFail()
    {
        $credential_retriever = Mockery::mock(CredentialRetriever::class);
        $credential_retriever->shouldReceive('authenticate')->once()->andThrow(CredentialAuthenticationException::class);
        $session_storage = [];

        $dynamic_session = new DynamicCredentialSession($session_storage, $credential_retriever);

        try {
            $dynamic_session->initialize('username', new ConcealedString('password'));
        } catch (CredentialAuthenticationException $ex) {
        }

        $this->expectException(DynamicCredentialSessionNotInitializedException::class);

        $dynamic_session->getAssociatedCredential();
    }
}
