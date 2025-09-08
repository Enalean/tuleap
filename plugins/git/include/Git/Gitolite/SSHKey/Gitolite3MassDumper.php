<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

class Gitolite3MassDumper implements MassDumper
{
    /**
     * @var Gitolite3Dumper
     */
    private $dumper;

    public function __construct(Gitolite3Dumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function dumpSSHKeys(InvalidKeysCollector $invalid_keys_collector)
    {
        try {
            $this->dumper->dumpKeys($invalid_keys_collector);
        } catch (DumpKeyException $ex) {
            return false;
        }
        return true;
    }
}
