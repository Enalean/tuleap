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

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class CredentialCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testQueryFailureIsConsideredAsDuplicateCredential()
    {
        $dao = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('save')->andThrow(\PDOException::class);
        $password_handler = Mockery::mock(\PasswordHandler::class);
        $password_handler->shouldReceive('computeHashPassword');
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract');

        $credential_creator = new CredentialCreator($dao, $password_handler, $identifier_extractor);

        $this->expectException(DuplicateCredentialException::class);

        $credential_creator->create('username', new ConcealedString('password'), new \DateTimeImmutable());
    }
}
