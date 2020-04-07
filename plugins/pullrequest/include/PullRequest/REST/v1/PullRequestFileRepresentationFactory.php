<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use RuntimeException;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class PullRequestFileRepresentationFactory
{

    /**
     * @var GitExec
     */
    private $executor;

    public function __construct(GitExec $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @throws \Tuleap\PullRequest\Exception\UnknownReferenceException
     */
    public function getModifiedFilesRepresentations(PullRequest $pull_request)
    {
        $x_files = [];

        $modified_files_name_status = $this->executor->getModifiedFilesNameStatus(
            $pull_request->getSha1Src(),
            $pull_request->getSha1Dest()
        );
        $modified_files_line_stat   = $this->executor->getModifiedFilesLineStat(
            $pull_request->getSha1Dest(),
            $pull_request->getSha1Src()
        );

        if (count($modified_files_name_status) !== count($modified_files_line_stat)) {
            throw new \RuntimeException(
                'Name status and line stat diff should be the same size, got ' . count($modified_files_name_status) .
                ' and ' . count($modified_files_line_stat)
            );
        }

        $modified_files_iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ALL);
        $modified_files_iterator->attachIterator(new \ArrayIterator($modified_files_name_status));
        $modified_files_iterator->attachIterator(new \ArrayIterator($modified_files_line_stat));

        foreach ($modified_files_iterator as list($name_status_line, $lines_stat_line)) {
            if (
                preg_match('/(?P<status>[A-Z])\\t(?P<file_name>.+)/', $name_status_line, $name_status) !== 1 ||
                preg_match(
                    '/(?P<added_lines>(\d|-)+)\\t(?P<removed_lines>(\d|-)+)\\t(?P<file_name>.+)/',
                    $lines_stat_line,
                    $lines_stat
                ) !== 1
            ) {
                throw new \RuntimeException('Not able to extract changes from the diff');
            }

            if ($name_status['file_name'] !== $lines_stat['file_name']) {
                throw new RuntimeException(
                    'The name status and short diff are not sorted the same way, ' .
                    "${name_status['file_name']} is not the same file than ${lines_stat['file_name']}"
                );
            }

            $file_representation = new PullRequestFileRepresentation();
            $file_representation->build(
                $name_status['file_name'],
                $name_status['status'],
                $lines_stat['added_lines'],
                $lines_stat['removed_lines']
            );
            $x_files[] = $file_representation;
        }

        return $x_files;
    }
}
