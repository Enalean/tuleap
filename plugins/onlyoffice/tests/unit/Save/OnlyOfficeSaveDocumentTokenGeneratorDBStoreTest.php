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

use DateInterval;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\Open\OnlyOfficeDocument;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeSaveDocumentTokenGeneratorDBStoreTest extends TestCase
{
    /**
     * @var OnlyOfficeSaveDocumentTokenDAO&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private OnlyOfficeSaveDocumentTokenGeneratorDBStore $token_generator;

    protected function setUp(): void
    {
        $this->dao             = $this->createMock(OnlyOfficeSaveDocumentTokenDAO::class);
        $this->token_generator = new OnlyOfficeSaveDocumentTokenGeneratorDBStore(
            $this->dao,
            new SplitTokenVerificationStringHasher(),
            new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentSave()),
            new DateInterval('PT1M')
        );
    }

    public function testTokenIsGeneratedAndStored(): void
    {
        $user                 = UserTestBuilder::buildWithDefaults();
        $item                 = new \Docman_Item(['item_id' => 258]);
        $server_id            = new UUIDTestContext();
        $only_office_document = new OnlyOfficeDocument(
            ProjectTestBuilder::aProject()->build(),
            $item,
            123,
            'document.docx',
            true,
            DocumentServer::withoutProjectRestrictions($server_id, 'https://example.com', new ConcealedString('very_secret')),
        );

        $this->dao->expects($this->once())
            ->method('create')
            ->with($user->getId(), $item->getId(), self::anything(), 70, $server_id)
            ->willReturn(147);

        $token = $this->token_generator->generateSaveToken(
            $user,
            $only_office_document,
            new \DateTimeImmutable('@10'),
        );

        self::assertNotNull($token);
        self::assertStringContainsString('147', $token->getString());
    }

    public function testDoesNotGenerateTokenForNonEditableDocument(): void
    {
        $user                 = UserTestBuilder::buildWithDefaults();
        $item                 = new \Docman_Item(['item_id' => 403]);
        $only_office_document = new OnlyOfficeDocument(
            ProjectTestBuilder::aProject()->build(),
            $item,
            123,
            'document.docx',
            false,
            DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString('very_secret')),
        );

        $token = $this->token_generator->generateSaveToken(
            $user,
            $only_office_document,
            new \DateTimeImmutable('@10'),
        );

        self::assertNull($token);
    }
}
