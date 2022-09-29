<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\XML;

use Tuleap\Tracker\XML\XMLUser;

final class XMLUserGroup
{
    /**
     * @readonly
     */
    public string $description = '';

    public function __construct(
        /**
         * @readonly
         */
        public string $name,
        /**
         * @var XMLUser[] $user
         */
        public array $users = [],
    ) {
    }

    public function export(\SimpleXMLElement $parent_node): \SimpleXMLElement
    {
        $ugroup = $parent_node->addChild('ugroup');
        $ugroup->addAttribute('name', $this->name);
        $ugroup->addAttribute('description', $this->description);

        $members = $ugroup->addChild('members');
        foreach ($this->users as $user) {
            $user->export('member', $members);
        }
        return $ugroup;
    }
}
