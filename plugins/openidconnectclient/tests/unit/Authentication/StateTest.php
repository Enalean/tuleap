<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesStateFromSignedState(): void
    {
        $secret_key  = 'Tuleap';
        $return_to   = '/return_to';
        $provider_id = 1234;
        $nonce       = 'random_string';

        $state        = new State($provider_id, $return_to, $secret_key, $nonce);
        $signed_state = $state->getSignedState();

        $this->assertEquals($state, State::createFromSignature($signed_state, $return_to, $secret_key, $nonce));
    }

    public function testCannotCreateFromSignatureWithInvalidSecretKey(): void
    {
        $state        = new State(12, '/return_to', 'key', 'random');
        $signed_state = $state->getSignedState();

        $this->expectException(\RuntimeException::class);
        State::createFromSignature($signed_state, '/return_to', 'invalid_key', 'random');
    }
}
