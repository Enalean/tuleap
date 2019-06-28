<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
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

class Controller_Commit extends ControllerBase // @codingStandardsIgnoreLine
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
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
        return 'tuleap/commit.tpl';
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
            return dgettext("gitphp", 'commit');
        }
        return 'commit';
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
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        } else {
            $this->params['hash'] = 'HEAD';
        }

        if (isset($_GET['o']) && ($_GET['o'] == 'jstip')) {
            $this->params['jstip'] = true;
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
        $commit = $this->project->GetCommit($this->params['hash']);
        $this->tpl->assign('commit', $commit);
        $this->tpl->assign('tree', $commit->GetTree());
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
        $this->tpl->assign('treediff', $treediff);
    }
}
