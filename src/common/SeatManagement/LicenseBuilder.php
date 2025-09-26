<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\SeatManagement;

final readonly class LicenseBuilder
{
    private const string PUBLIC_KEY_DIRECTORY = __DIR__ . '/keys';

    public function __construct(
        private CheckPublicKeyPresence $public_key_retriever,
    ) {
    }

    public function build(): License
    {
        $is_public_present = $this->public_key_retriever->checkPresence(self::PUBLIC_KEY_DIRECTORY);

        if (! $is_public_present) {
            return License::buildCommunityEdition();
        }

        return License::buildEnterpriseEdition();
    }
}
