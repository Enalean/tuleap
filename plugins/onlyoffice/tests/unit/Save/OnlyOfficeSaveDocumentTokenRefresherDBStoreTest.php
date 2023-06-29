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

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeSaveDocumentTokenRefresherDBStoreTest extends TestCase
{
    public function testUpdatesExpirationTime(): void
    {
        $token_verifier = $this->createStub(OnlyOfficeSaveDocumentTokenVerifier::class);
        $dao            = $this->createMock(OnlyOfficeSaveDocumentTokenDAO::class);

        $token_verifier->method('getDocumentSaveTokenData')->willReturn(new SaveDocumentTokenData(1, 102, 1, 1));
        $dao->expects(self::atLeastOnce())->method('updateTokensExpirationDate')->with(1, 1, 10, 20);

        $token_refresher = self::buildTokenRefresher($token_verifier, $dao, new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));

        $res = $token_refresher->refreshToken(new ConcealedString('save_token_raw_identifier'), new \DateTimeImmutable('@10'));

        self::assertTrue(Result::isOk($res));
    }

    public function testDoesNotUpdateAnythingWhenNoSplitTokenCanBeObtainedFromTheTokenRawIdentifier(): void
    {
        $token_refresher = self::buildTokenRefresher(
            $this->createStub(OnlyOfficeSaveDocumentTokenVerifier::class),
            $this->createStub(OnlyOfficeSaveDocumentTokenDAO::class),
            null,
        );

        $res = $token_refresher->refreshToken(new ConcealedString('not_valid'), new \DateTimeImmutable('@10'));

        self::assertTrue(Result::isErr($res));
    }

    public function testDoesNotUpdateAnythingWhenTheSaveTokenCannotBeFound(): void
    {
        $token_verifier = $this->createStub(OnlyOfficeSaveDocumentTokenVerifier::class);

        $token_verifier->method('getDocumentSaveTokenData')->willReturn(null);

        $token_refresher = self::buildTokenRefresher(
            $token_verifier,
            $this->createStub(OnlyOfficeSaveDocumentTokenDAO::class),
            new SplitToken(504, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
        );

        $res = $token_refresher->refreshToken(new ConcealedString('save_token_raw_identifier_expired'), new \DateTimeImmutable('@10'));

        self::assertTrue(Result::isErr($res));
    }

    private static function buildTokenRefresher(
        OnlyOfficeSaveDocumentTokenVerifier $token_verifier,
        OnlyOfficeSaveDocumentTokenDAO $dao,
        ?SplitToken $split_token,
    ): OnlyOfficeSaveDocumentTokenRefresherDBStore {
        return new OnlyOfficeSaveDocumentTokenRefresherDBStore(
            new class ($split_token) implements SplitTokenIdentifierTranslator {
                public function __construct(private ?SplitToken $split_token)
                {
                }

                public function getSplitToken(ConcealedString $identifier): SplitToken
                {
                    if ($this->split_token !== null) {
                        return $this->split_token;
                    }

                    throw new class extends SplitTokenException {
                    };
                }
            },
            $token_verifier,
            new \DateInterval('PT10S'),
            $dao
        );
    }
}
