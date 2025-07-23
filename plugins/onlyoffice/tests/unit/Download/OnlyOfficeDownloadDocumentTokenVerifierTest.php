<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Download;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeDownloadDocumentTokenVerifierTest extends TestCase
{
    /**
     * @var OnlyOfficeDownloadDocumentTokenDAO&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private SplitTokenVerificationStringHasher $hasher;
    private OnlyOfficeDownloadDocumentTokenVerifier $token_verifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao            = $this->createMock(OnlyOfficeDownloadDocumentTokenDAO::class);
        $this->hasher         = new SplitTokenVerificationStringHasher();
        $this->token_verifier = new OnlyOfficeDownloadDocumentTokenVerifier(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->hasher,
            new NullLogger(),
        );
    }

    public function testFindsDataAssociatedWithAValidToken(): void
    {
        $download_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(
            ['verifier' => $this->hasher->computeHash($download_token->getVerificationString()), 'user_id' => 102, 'document_id' => 11]
        );

        $token_data = $this->token_verifier->getDocumentDownloadTokenData($download_token, new \DateTimeImmutable('@20'));

        self::assertEquals(new DownloadDocumentTokenData(102, 11), $token_data);
    }

    public function testDoesNotRetrieveDataWhenTokenIsNotFound(): void
    {
        $download_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(null);

        self::assertNull($this->token_verifier->getDocumentDownloadTokenData($download_token, new \DateTimeImmutable('@20')));
    }

    public function testDoesNotRetrieveDataWhenTokenVerificationStringDoesNotMatch(): void
    {
        $download_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(
            ['verifier' => $this->hasher->computeHash(SplitTokenVerificationString::generateNewSplitTokenVerificationString()), 'user_id' => 102, 'document_id' => 11]
        );

        self::assertNull($this->token_verifier->getDocumentDownloadTokenData($download_token, new \DateTimeImmutable('@20')));
    }
}
