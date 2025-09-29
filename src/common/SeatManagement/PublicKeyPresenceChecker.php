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

use Override;
use function Psl\Filesystem\is_file;
use function Psl\Filesystem\read_directory;
use function Psl\Filesystem\exists;
use function Psl\Filesystem\is_directory;

final class PublicKeyPresenceChecker implements CheckPublicKeyPresence
{
    #[Override]
    public function checkPresence(string $public_key_directory): bool
    {
        if (! exists($public_key_directory) || ! is_directory($public_key_directory)) {
            return false;
        }

        $files = read_directory($public_key_directory);

        return array_any($files, static fn (string $filename) => str_ends_with($filename, '.key') && is_file($filename));
    }
}
