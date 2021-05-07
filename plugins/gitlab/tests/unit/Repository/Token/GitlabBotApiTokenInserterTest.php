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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Cryptography\KeyFactory;
use Mockery;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

class GitlabBotApiTokenInserterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabBotApiTokenInserter
     */
    private $inserter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabBotApiTokenDao
     */
    private $gitlab_bot_api_token_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|KeyFactory
     */
    private $key_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gitlab_bot_api_token_dao = Mockery::mock(GitlabBotApiTokenDao::class);
        $this->key_factory              = Mockery::mock(KeyFactory::class);

        $this->inserter = new GitlabBotApiTokenInserter(
            $this->gitlab_bot_api_token_dao,
            $this->key_factory
        );
    }

    public function testItInsertEncryptedToken(): void
    {
        $gitlab_repository = Mockery::mock(GitlabRepository::class, ['getId' => 123]);

        $token = new ConcealedString('myToken123');

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key
            ->shouldReceive('getRawKeyMaterial')
            ->andReturns(
                str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );

        $this->key_factory->shouldReceive('getEncryptionKey')->andReturn($encryption_key)->once();

        $this->gitlab_bot_api_token_dao
            ->shouldReceive('storeToken')
            ->with(
                123,
                \Mockery::on(
                    static function (string $encrypted_jira_token) use ($encryption_key) {
                        return SymmetricCrypto::decrypt($encrypted_jira_token, $encryption_key)->getString() === 'myToken123';
                    }
                )
            )
            ->once();

        $this->inserter->insertToken($gitlab_repository, $token);
    }
}
