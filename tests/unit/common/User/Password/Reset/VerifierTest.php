<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\UserTestBuilder;

final class VerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItGetsUserAssociatedWithToken(): void
    {
        $creation_date = new \DateTime();
        $dao           = $this->createMock(\Tuleap\User\Password\Reset\LostPasswordDAO::class);
        $dao->method('getTokenInformationById')->willReturn([
            'verifier'      => 'token_verification_part_password_hashed',
            'user_id'       => 101,
            'creation_date' => $creation_date->getTimestamp(),
        ]);

        $hasher = $this->createMock(SplitTokenVerificationStringHasher::class);
        $hasher->method('verifyHash')->willReturn(true);

        $user         = UserTestBuilder::aUser()->build();
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->with(101)->willReturn($user);

        $token = $this->createMock(SplitToken::class);
        $token->method('getID')->willReturn(1);
        $token->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));

        $token_verifier = new Verifier($dao, $hasher, $user_manager);

        self::assertEquals($user, $token_verifier->getUser($token));
    }

    public function testItThrowsAnExceptionWhenTokenIDCanNotBeFound(): void
    {
        $dao = $this->createMock(\Tuleap\User\Password\Reset\LostPasswordDAO::class);
        $dao->method('getTokenInformationById')->willReturn(null);

        $hasher = $this->createMock(SplitTokenVerificationStringHasher::class);
        $hasher->method('verifyHash')->willReturn(true);

        $user_manager = $this->createMock(\UserManager::class);

        $token = $this->createMock(SplitToken::class);
        $token->method('getID')->willReturn(1);

        $token_verifier = new Verifier($dao, $hasher, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\InvalidTokenException');
        $token_verifier->getUser($token);
    }

    public function testItThrowsAnExceptionWhenVerifierPartIsNotValid(): void
    {
        $dao = $this->createMock(\Tuleap\User\Password\Reset\LostPasswordDAO::class);
        $dao->method('getTokenInformationById')->willReturn(['verifier' => 'token_verification_part_password_hashed']);

        $hasher = $this->createMock(SplitTokenVerificationStringHasher::class);
        $hasher->method('verifyHash')->willReturn(false);

        $user_manager = $this->createMock(\UserManager::class);

        $token = $this->createMock(SplitToken::class);
        $token->method('getID')->willReturn(1);
        $token->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));

        $token_verifier = new Verifier($dao, $hasher, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\InvalidTokenException');
        $token_verifier->getUser($token);
    }

    public function testItThrowsAnExceptionWhenTheTokenIsExpired(): void
    {
        $expired_creation_date = new \DateTime();
        $expired_creation_date->sub(new \DateInterval(Verifier::TOKEN_VALIDITY_PERIOD));
        $expired_creation_date->sub(new \DateInterval(Verifier::TOKEN_VALIDITY_PERIOD));
        $dao = $this->createMock(\Tuleap\User\Password\Reset\LostPasswordDAO::class);
        $dao->method('getTokenInformationById')->willReturn([
            'verifier'      => 'token_verification_part_password_hashed',
            'user_id'       => 101,
            'creation_date' => $expired_creation_date->getTimestamp(),
        ]);

        $hasher = $this->createMock(SplitTokenVerificationStringHasher::class);
        $hasher->method('verifyHash')->willReturn(true);

        $user         = UserTestBuilder::aUser()->build();
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->with(101)->willReturn($user);

        $token = $this->createMock(SplitToken::class);
        $token->method('getID')->willReturn(1);
        $token->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));

        $token_verifier = new Verifier($dao, $hasher, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\ExpiredTokenException');
        $token_verifier->getUser($token);
    }
}
