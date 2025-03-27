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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\Cryptography\KeyFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IntegrationApiTokenInserterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var IntegrationApiTokenInserter
     */
    private $inserter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IntegrationApiTokenDao
     */
    private $integration_api_token_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&KeyFactory
     */
    private $key_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration_api_token_dao = $this->createMock(IntegrationApiTokenDao::class);
        $this->key_factory               = $this->createMock(KeyFactory::class);

        $this->inserter = new IntegrationApiTokenInserter(
            $this->integration_api_token_dao,
            $this->key_factory
        );
    }

    public function testItInsertEncryptedToken(): void
    {
        $gitlab_repository = $this->createStub(GitlabRepositoryIntegration::class);
        $gitlab_repository->method('getId')->willReturn(123);

        $token = new ConcealedString('myToken123');

        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $this->key_factory->expects($this->once())->method('getEncryptionKey')->willReturn($encryption_key);

        $this->integration_api_token_dao
            ->expects($this->once())
            ->method('storeToken')
            ->willReturnCallback(
                function (int $integration_id, string $encrypted_token) use ($encryption_key): void {
                    if ($integration_id !== 123 || SymmetricCrypto::decrypt($encrypted_token, $encryption_key)->getString() !== 'myToken123') {
                        throw new \RuntimeException('Received unexpected values to store');
                    }
                }
            );

        $this->inserter->insertToken($gitlab_repository, $token);
    }
}
