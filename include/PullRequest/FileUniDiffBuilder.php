<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use \diff_match_patch;

include PULLREQUEST_BASE_DIR . '/include/diff_match_patch/diff_match_patch.php';

class FileUniDiffBuilder
{
    const REMOVED = -1;
    const KEPT    =  0;
    const ADDED   =  1;

    public function buildFileUniDiff($old_content, $new_content)
    {
        $raw_diff       = $this->getRawDiffFromDiffMatchPatch($old_content, $new_content);
        $diff           = new FileUniDiff();
        $old_offset     = 0;
        $new_offset     = 0;
        $unidiff_offset = 0;

        foreach ($raw_diff as $tuple) {
            $lines = explode(PHP_EOL, $tuple[1]);
            $this->removeLastEmpty($lines);

            $type = $tuple[0];
            foreach ($lines as $line) {
                $unidiff_offset += 1;
                if ($type == self::REMOVED) {
                    $old_offset += 1;
                    $diff->addLine($type, $unidiff_offset, $old_offset, null, $line);
                } else if($type == self::KEPT) {
                    $new_offset += 1;
                    $old_offset += 1;
                    $diff->addLine($type, $unidiff_offset, $old_offset, $new_offset, $line);
                } else if($type == self::ADDED) {
                    $new_offset += 1;
                    $diff->addLine($type, $unidiff_offset, null, $new_offset, $line);
                }
            }
        }
        return $diff;
    }

    private function getRawDiffFromDiffMatchPatch($old_content, $new_content)
    {
        $differ   = new diff_match_patch();
        $lines    = $differ->diff_linesToChars($old_content, $new_content);
        $raw_diff = $differ->diff_main($lines[0], $lines[1]);

        $differ->diff_charsToLines($raw_diff, $lines[2]);
        return $raw_diff;
    }

    private function removeLastEmpty(array &$array)
    {
        $last = array_pop($array);
        if (! empty($last)) {
          $array[] = $last;
        }
    }
}
