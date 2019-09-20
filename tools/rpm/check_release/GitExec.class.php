<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
class GitExec
{

    public function hasChangedSince($path, $version)
    {
        $return_code;
        $output;
        exec("git diff --quiet $version -- $path", $output, $return_code);
        return $return_code;
    }

    public function fileContent($path, $version)
    {
        $output;
        exec("git show $version:$path", $output);
        return implode(PHP_EOL, $output);
    }

    public function lsRemote($remote)
    {
        $output;
        exec("git ls-remote $remote", $output);
        return $output;
    }
}
