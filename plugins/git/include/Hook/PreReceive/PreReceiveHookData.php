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

final class PreReceiveHookData
{
    /**
     * @var array<string,PreReceiveHookUpdatedReference>
     */
    public array $updated_references;

    public function __construct()
    {
        $this->updated_references = [];
    }

    public function addNewRev(string $ref_name, PreReceiveHookUpdatedReference $oldnew_values): void
    {
        $this->updated_references[$ref_name] = $oldnew_values;
    }
}
