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

use ForgeConfig;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\GitPHP\Ref;

class FilesHeaderPresenterBuilder
{
    /**
     * @var CommitForCurrentTreeRetriever
     */
    private $commit_retriever;

    public function __construct(CommitForCurrentTreeRetriever $commit_retriever)
    {
        $this->commit_retriever = $commit_retriever;
    }

    /**
     * @param HTTPRequest   $request
     * @param GitRepository $repository
     *
     * @return FilesHeaderPresenter
     */
    public function build(HTTPRequest $request, GitRepository $repository)
    {
        if (! ForgeConfig::get('git_repository_bp')) {
            return new FilesHeaderPresenter(false, '');
        }

        $action = $request->get('a');
        if ($action !== 'tree' && $action !== false) {
            return new FilesHeaderPresenter(false, '');
        }

        $head_name = $this->getHeadNameForCurrentCommit($request, $repository);

        return new FilesHeaderPresenter(true, $head_name);
    }

    /**
     * @param HTTPRequest   $request
     * @param GitRepository $repository
     *
     * @return string
     */
    private function getHeadNameForCurrentCommit(HTTPRequest $request, GitRepository $repository)
    {
        $commit = $this->commit_retriever->getCommitOfCurrentTree($request, $repository);
        if (! $commit) {
            return '';
        }

        /** @var Ref[] $refs */
        $refs = array_merge($commit->GetHeads(), $commit->GetTags());
        if (empty($refs)) {
            return $commit->GetHash();
        }

        $refs_names = array_map(
            function (Ref $ref) {
                return $ref->GetName();
            },
            $refs
        );
        $requested_hashbase = preg_replace('%^refs/(?:tags|heads)/%', '', $request->get('hb'));
        if (array_search($requested_hashbase, $refs_names) !== false) {
            return $requested_hashbase;
        }

        return $refs_names[0];
    }
}
