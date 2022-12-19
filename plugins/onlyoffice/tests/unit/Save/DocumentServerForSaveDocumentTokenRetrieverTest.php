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
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\PHPUnit\TestCase;

final class DocumentServerForSaveDocumentTokenRetrieverTest extends TestCase
{
    private const SERVER_1_ID          = 1;
    private const SERVER_2_ID          = 2;
    private const UNEXISTING_SERVER_ID = 3;

    public function testExceptionIfServerIsNotFound(): void
    {
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith(
                new DocumentServer(self::SERVER_1_ID, 'https://example.com/1', new ConcealedString('very_secret')),
                new DocumentServer(self::SERVER_2_ID, 'https://example.com/2', new ConcealedString('much_secret')),
            )
        );

        $this->expectException(DocumentServerNotFoundException::class);

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, self::UNEXISTING_SERVER_ID)
        );
    }

    public function testThrowsExceptionIfServerHasNoKey(): void
    {
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith(
                new DocumentServer(self::SERVER_1_ID, 'https://example.com/1', new ConcealedString('very_secret')),
                new DocumentServer(self::SERVER_2_ID, 'https://example.com/2', new ConcealedString(''))
            ),
        );

        $this->expectException(DocumentServerHasNoExistingSecretException::class);

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, self::SERVER_2_ID)
        );
    }

    public function testItReturnsTheServer(): void
    {
        $server_1  = new DocumentServer(self::SERVER_1_ID, 'https://example.com/1', new ConcealedString('very_secret'));
        $server_2  = new DocumentServer(self::SERVER_2_ID, 'https://example.com/2', new ConcealedString('much_secret'));
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith($server_1, $server_2),
        );

        self::assertEquals(
            $server_2,
            $retriever->getServerFromSaveToken(
                new SaveDocumentTokenData(123, 102, 103, self::SERVER_2_ID)
            )
        );
    }

    public function testItReturnsTheFirstServerIfSaveTokenHasBeenCreatedWhenForgeconfigWereStoringJwtKey(): void
    {
        $server_1  = new DocumentServer(self::SERVER_1_ID, 'https://example.com/1', new ConcealedString('very_secret'));
        $server_2  = new DocumentServer(self::SERVER_2_ID, 'https://example.com/2', new ConcealedString('much_secret'));
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith($server_1, $server_2),
        );

        self::assertEquals(
            $server_1,
            $retriever->getServerFromSaveToken(
                new SaveDocumentTokenData(123, 102, 103, 0)
            )
        );
    }

    public function testThrowsExceptionIfTheFirstServerHasNoKeyAndSaveTokenHasBeenCreatedWhenForgeconfigWereStoringJwtKey(): void
    {
        $server_1  = new DocumentServer(self::SERVER_1_ID, 'https://example.com/1', new ConcealedString(''));
        $server_2  = new DocumentServer(self::SERVER_2_ID, 'https://example.com/2', new ConcealedString('much_secret'));
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWith($server_1, $server_2),
        );

        $this->expectException(DocumentServerHasNoExistingSecretException::class);

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, 0)
        );
    }

    public function testThrowsExceptionIfThereIsntAnyServerAndSaveTokenHasBeenCreatedWhenForgeconfigWereStoringJwtKey(): void
    {
        $retriever = new DocumentServerForSaveDocumentTokenRetriever(
            IRetrieveDocumentServersStub::buildWithoutServer(),
        );

        $this->expectException(NoDocumentServerException::class);

        $retriever->getServerFromSaveToken(
            new SaveDocumentTokenData(123, 102, 103, 0)
        );
    }
}
