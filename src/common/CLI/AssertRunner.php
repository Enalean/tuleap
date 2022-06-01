<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CLI;

final class AssertRunner
{
    private function __construct(private string $username)
    {
    }

    public static function asHTTPUser(): self
    {
        return new self(\ForgeConfig::get('sys_http_user'));
    }

    public static function asCurrentProcessUser(): self
    {
        return new self(posix_getpwuid(posix_geteuid())['name']);
    }

    public function assertProcessIsExecutedByExpectedUser(): void
    {
        $user = posix_getpwuid(posix_geteuid());
        if ($user['name'] !== $this->username) {
            throw new \RuntimeException(sprintf('Command must be run by %s', $this->username));
        }
    }
}
