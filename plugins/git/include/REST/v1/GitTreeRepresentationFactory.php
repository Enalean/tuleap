<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\Project;

class GitTreeRepresentationFactory
{
    private const string TREE_TYPE = 'tree';
    private const string BLOB_TYPE = 'blob';

    /**
     * @throws \GitRepositoryException
     * @throws GitObjectTypeNotSupportedException
     * @return GitTreeRepresentation[]
     */
    public function getGitTreeRepresentation(string $path, string $ref, Project $git_repository): array
    {
        $commit = $git_repository->GetCommit($ref);

        if ($commit === null) {
            throw new \GitRepositoryException(sprintf('Commit for the reference \'%s\' not found', $ref));
        }

        if (empty($path)) {
            $hash = $commit->GetTree()->GetHash();
        } else {
            $hash = $commit->PathToHash($path);
        }

        $git_repository->GetObject($hash, $type_int_tree);

        if ($type_int_tree !== Pack::OBJ_TREE) {
            throw new \GitRepositoryException(sprintf('Path \'%s\' is not a directory', $path));
        }

        $tree_contents = $git_repository->GetTree($hash)->GetContents();

        $tree = [];
        foreach ($tree_contents as $content) {
            $content_hash = $content->GetHash();
            $git_repository->GetObject($content_hash, $type_int);
            switch ($type_int) {
                case Pack::OBJ_TREE:
                    $type = self::TREE_TYPE;
                    break;
                case Pack::OBJ_BLOB:
                    $type = self::BLOB_TYPE;
                    break;
                default:
                    throw new GitObjectTypeNotSupportedException();
            }

            $git_object_path = empty($path) ? $content->GetPath() : $path . '/' . $content->GetPath();
            $tree[]          =  new GitTreeRepresentation($content->GetMode(), $type, $content->GetName(), $git_object_path, $content_hash);
        }
        return $tree;
    }
}
