<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace GitPHP\Commit;

use Tuleap\Git\GitPHP\GitObjectType;
use Tuleap\Git\GitPHP\Tree;

class TreePresenter
{
    /**
     * @var GitObjectType[]
     */
    public $sorted_content;

    public function __construct(Tree $tree)
    {
        $this->sorted_content = $tree->GetContents();

        usort($this->sorted_content, function (GitObjectType $a, GitObjectType $b) {
            $are_same_type = ($a->isBlob() && $b->isBlob())
                || ($a->isSubmodule() && $b->isSubmodule())
                || ($a->isTree() && $b->isTree());
            if ($are_same_type) {
                return strnatcasecmp($a->GetName(), $b->GetName());
            }

            if ($a->isTree()) {
                return -1;
            }

            if ($b->isTree()) {
                return 1;
            }

            if ($a->isSubmodule()) {
                return -1;
            }

            if ($b->isSubmodule()) {
                return 1;
            }

            return 1;
        });
    }
}
