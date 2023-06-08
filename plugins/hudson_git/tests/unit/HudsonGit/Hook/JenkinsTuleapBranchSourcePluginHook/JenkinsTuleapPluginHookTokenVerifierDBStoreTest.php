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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

final class JenkinsTuleapPluginHookTokenVerifierDBStoreTest extends TestCase
{
    /**
     * @var \\PHPUnit\Framework\MockObject\MockObject&JenkinsTuleapPluginHookTokenDAO
     */
    private $dao;
    private PrefixedSplitTokenSerializer $serializer;
    private SplitTokenVerificationStringHasher $hasher;
    private JenkinsTuleapPluginHookTokenVerifierDBStore $verifier;

    protected function setUp(): void
    {
        $this->dao        = $this->createMock(JenkinsTuleapPluginHookTokenDAO::class);
        $this->serializer = new PrefixedSplitTokenSerializer(new JenkinsTuleapPluginHookPrefixToken());
        $this->hasher     = new SplitTokenVerificationStringHasher();
        $this->verifier   = new JenkinsTuleapPluginHookTokenVerifierDBStore(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->serializer,
            $this->hasher,
            new NullLogger()
        );
    }

    public function testAcceptsValidToken(): void
    {
        $raw_verification_string = str_repeat('a', SplitTokenVerificationString::VERIFICATION_STRING_LENGTH);
        $verification_string     = new SplitTokenVerificationString(new ConcealedString($raw_verification_string));
        $token                   = $this->serializer->getIdentifier(
            new SplitToken(
                12,
                $verification_string,
            )
        );

        $this->dao->expects(self::once())->method('searchTokenVerification')->willReturn(['verifier' => $this->hasher->computeHash($verification_string)]);
        $this->dao->expects(self::once())->method('deleteTokenByID');

        self::assertTrue($this->verifier->isTriggerTokenValid($token, new \DateTimeImmutable('@10')));
    }

    public function testRejectsIncorrectlyFormattedToken(): void
    {
        self::assertFalse($this->verifier->isTriggerTokenValid(new ConcealedString('wrong'), new \DateTimeImmutable('@10')));
    }

    public function testRejectsUnknownToken(): void
    {
        $raw_verification_string = str_repeat('a', SplitTokenVerificationString::VERIFICATION_STRING_LENGTH);
        $verification_string     = new SplitTokenVerificationString(new ConcealedString($raw_verification_string));
        $token                   = $this->serializer->getIdentifier(
            new SplitToken(
                404,
                $verification_string,
            )
        );

        $this->dao->expects(self::once())->method('searchTokenVerification')->willReturn(null);

        self::assertFalse($this->verifier->isTriggerTokenValid($token, new \DateTimeImmutable('@10')));
    }

    public function testRejectsTokenNotMatchingTheVerificationString(): void
    {
        $raw_verification_string = str_repeat('a', SplitTokenVerificationString::VERIFICATION_STRING_LENGTH);
        $verification_string     = new SplitTokenVerificationString(new ConcealedString($raw_verification_string));
        $token                   = $this->serializer->getIdentifier(
            new SplitToken(
                13,
                $verification_string,
            )
        );

        $this->dao->expects(self::once())->method('searchTokenVerification')->willReturn(['verifier' => 'somethingelse']);

        self::assertFalse($this->verifier->isTriggerTokenValid($token, new \DateTimeImmutable('@10')));
    }
}
