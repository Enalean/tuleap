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

class FileUniDiffBuilder
{
    private $REMOVED = '-';
    private $KEPT    = ' ';
    private $ADDED   = '+';

    public function buildFileNullDiff()
    {
        return new FileNullDiff();
    }

    public function buildFileUniDiff(GitExec $git_exec, $file_path, $old_rev, $new_rev)
    {
        $raw_diff = $git_exec->unidiff($file_path, $old_rev, $new_rev);
        return $this->build($raw_diff);
    }

    public function buildFileUniDiffFromCommonAncestor(GitExec $git_exec, $file_path, $old_rev, $new_rev)
    {
        $raw_diff = $git_exec->unidiffFromCommonAncestor($file_path, $old_rev, $new_rev);
        return $this->build($raw_diff);
    }

    private function build($raw_diff)
    {
        $diff = new FileUniDiff();

        $old_offset     = 0;
        $new_offset     = 0;
        $unidiff_offset = 0;

        $i = $this->getUniDiffStartLineIndex($raw_diff);
        $raw_diff_count = count($raw_diff);
        while ($i < $raw_diff_count) {
            $line = $raw_diff[$i];
            $i   += 1;

            if (strlen($line) == 0) {
                $type = $this->KEPT;
                $line = '';
            } else {
                $type = $line[0];
                $line = substr($line, 1);
                if ($line == false) {
                    $line = '';
                }
            }

            $unidiff_offset += 1;
            if ($type == $this->REMOVED) {
                $old_offset += 1;
                $diff->addLine(UniDiffLine::REMOVED, $unidiff_offset, $old_offset, null, $line);
            } elseif ($type == $this->KEPT) {
                $new_offset += 1;
                $old_offset += 1;
                $diff->addLine(UniDiffLine::KEPT, $unidiff_offset, $old_offset, $new_offset, $line);
            } elseif ($type == $this->ADDED) {
                $new_offset += 1;
                $diff->addLine(UniDiffLine::ADDED, $unidiff_offset, null, $new_offset, $line);
            }
        }
        return $diff;
    }

    private function getUniDiffStartLineIndex($raw_diff)
    {
        $i = 0;
        $raw_diff_count = count($raw_diff);
        while ($i < $raw_diff_count) {
            $line = substr($raw_diff[$i], 0, 2);
            $i   += 1;
            if ($line == '@@') {
                break;
            }
        }
        return $i;
    }
}
