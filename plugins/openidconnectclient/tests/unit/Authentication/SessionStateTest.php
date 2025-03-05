<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SessionStateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderSessionStates')]
    public function testCanBeTransformedToAMinimalRepresentationAndBuiltFromIt(SessionState $session_state): void
    {
        $representation        = $session_state->convertToMinimalRepresentation();
        $rebuilt_session_state = SessionState::buildFromMinimalRepresentation($representation);

        self::assertSame($session_state->getSecretKey(), $rebuilt_session_state->getSecretKey());
        self::assertSame($session_state->getNonce(), $rebuilt_session_state->getNonce());
        self::assertSame($session_state->getReturnTo(), $rebuilt_session_state->getReturnTo());
        $this->assertTrue($session_state->getPKCECodeVerifier()->isIdenticalTo($rebuilt_session_state->getPKCECodeVerifier()));
    }

    public static function dataProviderSessionStates(): array
    {
        return [
            [new SessionState('secret_key', 'return_to', 'nonce', new ConcealedString('code_verifier'))],
            [new SessionState('secret_key', null, 'nonce', new ConcealedString('code_verifier'))],
        ];
    }

    public function testBuildingFromAnInvalidRepresentationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $representation = new \stdClass();
        SessionState::buildFromMinimalRepresentation($representation);
    }
}
