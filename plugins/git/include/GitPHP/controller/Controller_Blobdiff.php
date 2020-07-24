<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

use GitPHP\Commit\CommitPresenter;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use UserManager;

class Controller_Blobdiff extends Controller_DiffBase // @codingStandardsIgnoreLine
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->project) {
            throw new MessageException(dgettext("gitphp", 'Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate() // @codingStandardsIgnoreLine
    {
        if (! isset($this->params['sidebyside'])) {
            return 'tuleap/blob-diff.tpl';
        }

        return 'tuleap/blob-diff-side-by-side.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param bool $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        if ($local) {
            return dgettext("gitphp", 'blobdiff');
        }
        return 'blobdiff';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
        parent::ReadQuery();

        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        }
        if (isset($_GET['hp'])) {
            $this->params['hashparent'] = $_GET['hp'];
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['file'])) {
            $this->tpl->assign('file', $this->params['file']);
        }

        $filediff = new FileDiff($this->project, $this->params['hashparent'], $this->params['hash']);
        $this->tpl->assign('filediff', $filediff);

        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return;
        }

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $this->tpl->assign('sidebyside', true);
        }

        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        $blobparent = $this->project->GetBlob($this->params['hashparent']);
        $blobparent->SetCommit($commit);
        $blobparent->SetPath($this->params['file']);
        $this->tpl->assign('blobparent', $blobparent);

        $blob = $this->project->GetBlob($this->params['hash']);
        $blob->SetPath($this->params['file']);
        $this->tpl->assign('blob', $blob);

        $tree = $commit->GetTree();
        $this->tpl->assign('tree', $tree);

        $blob->SetCommit($commit);
        $treediff = $commit->DiffToParent();
        $treediff->SetRenames(true);
        $commit_metadata_retriever = new CommitMetadataRetriever(
            new CommitStatusRetriever(new CommitStatusDAO()),
            UserManager::instance()
        );
        $commit_metadata = $commit_metadata_retriever->getMetadataByRepositoryAndCommits(
            $this->getTuleapGitRepository(),
            $commit
        );
        $commit_presenter = new CommitPresenter($commit, $commit_metadata[0], $treediff);
        $this->tpl->assign('commit_presenter', $commit_presenter);
    }
}
