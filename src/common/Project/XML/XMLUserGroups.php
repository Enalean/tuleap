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

namespace Tuleap\Project\XML;

use Tuleap\Project\UGroups\XML\XMLUserGroup;

final class XMLUserGroups
{
    public function __construct(
        /**
         * @readonly
         * @var XMLUserGroup[] $user_groups
         */
        public array $user_groups = [],
    ) {
    }

    public function export(\SimpleXMLElement $parent_node): ?\SimpleXMLElement
    {
        if (count($this->user_groups) === 0) {
            return null;
        }
        $ugroups = $parent_node->addChild('ugroups');
        foreach ($this->user_groups as $user_group) {
            $user_group->export($ugroups);
        }
        return $ugroups;
    }
}
