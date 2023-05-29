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

require_once __DIR__ . '/../bootstrap.php';

use Tuleap\Cryptography\ConcealedString;

final class CredentialCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testQueryFailureIsConsideredAsDuplicateCredential(): void
    {
        $dao = $this->createMock(CredentialDAO::class);
        $dao->method('save')->willThrowException(new \PDOException());
        $password_handler = $this->createMock(\PasswordHandler::class);
        $password_handler->method('computeHashPassword');
        $identifier_extractor = $this->createMock(CredentialIdentifierExtractor::class);
        $identifier_extractor->method('extract');

        $credential_creator = new CredentialCreator($dao, $password_handler, $identifier_extractor);

        $this->expectException(DuplicateCredentialException::class);

        $credential_creator->create('username', new ConcealedString('password'), new \DateTimeImmutable());
    }
}
