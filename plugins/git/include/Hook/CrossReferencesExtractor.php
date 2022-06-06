<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Hook;

use Git;
use Git_Exec;

/**
 * Extract references usage in commit messages
 */
class CrossReferencesExtractor
{
    public function __construct(private Git_Exec $git_exec, private \ReferenceManager $reference_manager)
    {
    }

    /**
     * @throws \Git_Command_Exception
     */
    public function extractCommitReference(PushDetails $push_details, string $commit_sha1): void
    {
        $rev_id = $push_details->getRepository()->getFullName() . '/' . $commit_sha1;
        $text   = $this->git_exec->catFile($commit_sha1);
        $this->reference_manager->extractCrossRef(
            $text,
            $rev_id,
            Git::REFERENCE_NATURE,
            (int) $push_details->getRepository()->getProject()->getId(),
            (int) $push_details->getUser()->getId()
        );
    }

    /**
     * @throws \Git_Command_Exception
     */
    public function extractTagReference(PushDetails $push_details): void
    {
        $tag_reference = $push_details->getRefname();
        if (strpos($tag_reference, 'refs/tags') === 0) {
            $tag_name = str_replace('refs/tags/', '', $tag_reference);
        } else {
            $tag_name = $tag_reference;
        }

        $rev_id = $push_details->getRepository()->getFullName() . '/' . $tag_name;
        $text   = $this->git_exec->catFile($tag_name);
        $this->reference_manager->extractCrossRef(
            $text,
            $rev_id,
            Git::TAG_REFERENCE_NATURE,
            (int) $push_details->getRepository()->getProject()->getId(),
            (int) $push_details->getUser()->getId()
        );
    }
}
