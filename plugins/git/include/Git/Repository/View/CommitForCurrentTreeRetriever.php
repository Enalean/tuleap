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

namespace Tuleap\Git\Repository\View;

use GitRepository;
use HTTPRequest;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\ProjectProvider;

class CommitForCurrentTreeRetriever
{
    /**
     * @param HTTPRequest   $request
     * @param GitRepository $repository
     *
     * @return Commit|null
     */
    public function getCommitOfCurrentTree(HTTPRequest $request, GitRepository $repository)
    {
        $hashbase = 'HEAD';
        if ($request->exist('h')) {
            $hashbase = $request->get('h');
        }
        if ($request->exist('hb')) {
            $hashbase = $request->get('hb');
        }

        $provider       = new ProjectProvider($repository);
        $gitphp_project = $provider->GetProject();

        return $gitphp_project->GetCommit($hashbase);
    }
}
