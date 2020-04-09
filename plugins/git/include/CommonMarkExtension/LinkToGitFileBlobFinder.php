<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Git\CommonMarkExtension;

use Tuleap\Git\GitPHP\Commit;
use Tuleap\URI\URIModifier;

class LinkToGitFileBlobFinder
{
    /**
     * @var string
     */
    private $current_path;
    /**
     * @var Commit
     */
    private $current_commit;

    public function __construct(string $current_path, Commit $current_commit)
    {
        $this->current_path   = $current_path;
        $this->current_commit = $current_commit;
    }

    public function findBlob(string $url): ?BlobPointedByURL
    {
        if (strpos($url, '/') !== 0) {
            $current_dir_full_path = dirname('/' . $this->current_path);
            $url = $current_dir_full_path . '/' . $url;
        }
        $url = URIModifier::removeDotSegments($url);

        $project      = $this->current_commit->GetProject();
        $path_in_repo = ltrim($url, '/');

        $blob = $project->GetBlob($this->current_commit->PathToHash($path_in_repo));
        if ($blob === null) {
            return null;
        }

        return new BlobPointedByURL($blob->GetHash(), $this->current_commit->GetHash(), $path_in_repo);
    }
}
