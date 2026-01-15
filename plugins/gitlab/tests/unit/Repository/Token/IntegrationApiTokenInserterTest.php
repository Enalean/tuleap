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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IntegrationApiTokenInserterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IntegrationApiTokenInserter $inserter;
    private IntegrationApiTokenDao&MockObject $integration_api_token_dao;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->integration_api_token_dao = $this->createMock(IntegrationApiTokenDao::class);

        $this->inserter = new IntegrationApiTokenInserter(
            $this->integration_api_token_dao,
        );
    }

    public function testItInsertEncryptedToken(): void
    {
        $gitlab_repository = $this->createStub(GitlabRepositoryIntegration::class);
        $gitlab_repository->method('getId')->willReturn(123);

        $token = new ConcealedString('myToken123');

        $this->integration_api_token_dao
            ->expects($this->once())
            ->method('storeToken')
            ->willReturnCallback(
                function (int $integration_id, ConcealedString $token): void {
                    if ($integration_id !== 123 || ! $token->isIdenticalTo(new ConcealedString('myToken123'))) {
                        throw new \RuntimeException('Received unexpected values to store');
                    }
                }
            );

        $this->inserter->insertToken($gitlab_repository, $token);
    }
}
