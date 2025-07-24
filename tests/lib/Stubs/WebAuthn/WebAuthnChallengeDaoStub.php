<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\WebAuthn;

use Tuleap\Option\Option;
use Tuleap\WebAuthn\Challenge\RetrieveWebAuthnChallenge;
use Tuleap\WebAuthn\Challenge\SaveWebAuthnChallenge;
use function Psl\Type\string;

final class WebAuthnChallengeDaoStub implements SaveWebAuthnChallenge, RetrieveWebAuthnChallenge
{
    public int $user_id_saved = -1;

    public ?string $challenge_saved = null;

    #[\Override]
    public function saveChallenge(int $user_id, string $challenge): void
    {
        $this->user_id_saved   = $user_id;
        $this->challenge_saved = $challenge;
    }

    #[\Override]
    public function searchChallenge(int $user_id): Option
    {
        if ($user_id === $this->user_id_saved && is_string($this->challenge_saved)) {
            return Option::fromValue($this->challenge_saved);
        }

        return Option::nothing(string());
    }
}
