<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git;

final class GitXMLImportDefaultBranchRetriever implements RetrieveGitDefaultBranchInXMLImport
{
    private const LEGACY_DEFAULT_GIT_BRANCH = 'master';
    private const DEFAULT_GIT_BRANCH        = 'main';

    public function retrieveDefaultBranchFromXMLContent(\Git_Exec $git_exec, \SimpleXMLElement $repository_info): string
    {
        $all_branches = $git_exec->getAllBranchesSortedByCreationDate();
        if (empty($all_branches)) {
            return '';
        }

        $new_default_branch = $all_branches[0];
        if (in_array(self::LEGACY_DEFAULT_GIT_BRANCH, $all_branches)) {
            $new_default_branch = self::LEGACY_DEFAULT_GIT_BRANCH;
        }
        if (in_array(self::DEFAULT_GIT_BRANCH, $all_branches)) {
            $new_default_branch = self::DEFAULT_GIT_BRANCH;
        }

        if (isset($repository_info['default_branch'])) {
            $xml_default_branch = (string) $repository_info['default_branch'];
            if (in_array($xml_default_branch, $all_branches)) {
                $new_default_branch = $xml_default_branch;
            }
        }

        return $new_default_branch;
    }
}
