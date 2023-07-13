<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

/**
 * @psalm-immutable
*/
final class PreReceiveHookData
{
    /**
     * @param array<string,PreReceiveHookUpdatedReference> $updated_references
     */
    private function __construct(public readonly array $updated_references, public readonly string $repository_path)
    {
    }

    /**
     * @psalm-return Ok<self>|Err<Fault>
     */
    public static function fromRawStdinHook(string $stdin, string $repository_path, string $guest_repository_path, LoggerInterface $logger): Ok|Err
    {
        $updated_references = [];
        $separator          = "\r\n";
        $line               = strtok($stdin, $separator);

        while ($line !== false) {
            $revs = explode(' ', trim($line));

            if (count($revs) === 3) {
                $logger->debug("[pre-receive] - $revs[0] $revs[1] $revs[2]");
                $updated_references[$revs[2]] = new PreReceiveHookUpdatedReference($revs[0], $revs[1]);
            } else {
                $logger->error("[pre-receive] Failed to provide 3 arguments on STDIN");
                return Result::err(Fault::fromMessage("Wrong number of arguments submitted, three arguments of the form old_rev new_rev refname expected on STDIN"));
            }

            $line = strtok($separator);
        }

        return Result::ok(new self($updated_references, $guest_repository_path));
    }
}
