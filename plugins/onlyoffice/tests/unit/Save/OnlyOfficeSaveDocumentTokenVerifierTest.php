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

namespace Tuleap\OnlyOffice\Save;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeSaveDocumentTokenVerifierTest extends TestCase
{
    /**
     * @var OnlyOfficeSaveDocumentTokenDAO&\PHPUnit\Framework\MockObject\Stub
     */
    private $dao;
    private SplitTokenVerificationStringHasher $hasher;
    private OnlyOfficeSaveDocumentTokenVerifier $token_verifier;

    protected function setUp(): void
    {
        $this->dao            = $this->createStub(OnlyOfficeSaveDocumentTokenDAO::class);
        $this->hasher         = new SplitTokenVerificationStringHasher();
        $this->token_verifier = new OnlyOfficeSaveDocumentTokenVerifier(
            $this->dao,
            $this->hasher,
            new NullLogger(),
        );
    }

    public function testFindsDataAssociatedWithAValidToken(): void
    {
        $save_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $server_id  = new UUIDTestContext();

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(
            [
                'verifier'    => $this->hasher->computeHash($save_token->getVerificationString()),
                'user_id'     => 102,
                'document_id' => 11,
                'server_id'   => $server_id,
            ]
        );

        $token_data = $this->token_verifier->getDocumentSaveTokenData($save_token, new \DateTimeImmutable('@20'));

        self::assertEquals(new SaveDocumentTokenData(1, 102, 11, $server_id), $token_data);
    }

    public function testDoesNotRetrieveDataWhenTokenIsNotFound(): void
    {
        $save_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(null);

        self::assertNull($this->token_verifier->getDocumentSaveTokenData($save_token, new \DateTimeImmutable('@20')));
    }

    public function testDoesNotRetrieveDataWhenTokenVerificationStringDoesNotMatch(): void
    {
        $save_token = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());

        $this->dao->method('searchTokenVerificationAndAssociatedData')->willReturn(
            [
                'verifier'    => $this->hasher->computeHash(
                    SplitTokenVerificationString::generateNewSplitTokenVerificationString()
                ),
                'user_id'     => 102,
                'document_id' => 11,
                'server_id'   => 1,
            ]
        );

        self::assertNull($this->token_verifier->getDocumentSaveTokenData($save_token, new \DateTimeImmutable('@20')));
    }
}
