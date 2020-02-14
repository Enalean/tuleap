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

namespace Tuleap\Authentication\SplitToken;

class SplitTokenVerificationStringHasher
{
    public function computeHash(SplitTokenVerificationString $verification_string): string
    {
        return hash('sha256', $verification_string->getString());
    }

    public function verifyHash(SplitTokenVerificationString $verification_string, string $known_verification_string): bool
    {
        return hash_equals($known_verification_string, $this->computeHash($verification_string));
    }
}
