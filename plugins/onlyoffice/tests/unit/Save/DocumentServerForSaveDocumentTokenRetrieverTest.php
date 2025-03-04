<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\UUID;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentServerForSaveDocumentTokenRetrieverTest extends TestCase
{
    public function testExceptionIfServerIsNotFound(): void
    {
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith(
                DocumentServer::withoutProjectRestrictions($this->getServer1ID(), 'https://example.com/1', new ConcealedString('very_secret')),
                DocumentServer::withoutProjectRestrictions($this->getServer2ID(), 'https://example.com/2', new ConcealedString('much_secret')),
            )
        );

        $this->expectException(DocumentServerNotFoundException::class);

        $not_existing_server_id = new UUIDTestContext();

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, $not_existing_server_id)
        );
    }

    public function testThrowsExceptionIfServerHasNoKey(): void
    {
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith(
                DocumentServer::withoutProjectRestrictions($this->getServer1ID(), 'https://example.com/1', new ConcealedString('very_secret')),
                DocumentServer::withoutProjectRestrictions($this->getServer2ID(), 'https://example.com/2', new ConcealedString(''))
            ),
        );

        $this->expectException(DocumentServerHasNoExistingSecretException::class);

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, $this->getServer2ID())
        );
    }

    public function testItReturnsTheServer(): void
    {
        $server_1  = DocumentServer::withoutProjectRestrictions($this->getServer1ID(), 'https://example.com/1', new ConcealedString('very_secret'));
        $server_2  = DocumentServer::withoutProjectRestrictions($this->getServer2ID(), 'https://example.com/2', new ConcealedString('much_secret'));
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith($server_1, $server_2),
        );

        self::assertEquals(
            $server_2,
            $retriever->getServerFromSaveToken(
                new SaveDocumentTokenData(123, 102, 103, $this->getServer2ID())
            )
        );
    }

    private function getServer1ID(): UUID
    {
        static $id = null;
        if ($id === null) {
            $id = new UUIDTestContext();
        }
        return $id;
    }

    private function getServer2ID(): UUID
    {
        static $id = null;
        if ($id === null) {
            $id = new UUIDTestContext();
        }
        return $id;
    }
}
