<?php
/**
  * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\Svn\Repository;

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class RepositoryRegexpBuilder {
    public function generateRegexpFromPath($path, LegacyDataAccessInterface $data_access)
    {
        # Split a given path into subpathes according to depth, then build a regular expression like below:
        # Path: '/trunk/src/common/' =>
        # Regex: '^(/(trunk|\\*))$|^(/(trunk|\\*)/)$|^(/(trunk|\\*)/(src|\\*))$|^(/(trunk|\\*)/(src|\\*)/)$|^(/(trunk|\\*)/(src|\\*)/(common|\\*))$|^(/(trunk|\\*)/(src|\\*)/(common|\\*)/)$'
        $list_repositories = explode('/', $path);
        $star_operator     = "\\*";

        $root            = "/";
        $pattern_matcher = '';
        $pattern_builder = '';
        foreach ($list_repositories as $dir_val) {
            if ($dir_val !== "") {
                $pattern_builder .= $root.'('.$data_access->escapeLikeValue($dir_val).'|'. $star_operator .')';

                if ($pattern_matcher !== '') {
                    $pattern_matcher .= '|^('.$pattern_builder.')$|^('.$pattern_builder.'/)$';
                } else {
                    $pattern_matcher .= '^('.$pattern_builder.')$|^('.$pattern_builder.'/)$';
                }
            }
        }

        return $pattern_matcher;
    }
}
