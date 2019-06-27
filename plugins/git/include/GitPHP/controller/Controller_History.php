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

use GitPHP\Shortlog\ShortlogPresenterBuilder;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use UserManager;

/**
 * History controller class
 *
 */
class Controller_History extends ControllerBase // @codingStandardsIgnoreLine
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
        return 'tuleap/history.tpl';
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
            return dgettext("gitphp", 'history');
        }
        return 'history';
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
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        } else {
            $this->params['hashbase'] = 'HEAD';
        }
        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['pg'])) {
            $this->params['page'] = $_GET['pg'];
        } else {
            $this->params['page'] = 0;
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
        $co = $this->project->GetCommit($this->params['hashbase']);
        if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
            $this->params['hash'] = $co->PathToHash($this->params['file']);
        }

        $this->tpl->assign('commit', $co);
        $this->tpl->assign('tree', $co->GetTree());
        $this->tpl->assign('hashbase', $this->params['hashbase']);
        $this->tpl->assign('page', $this->params['page']);

        $blob = $this->project->GetBlob($this->params['hash']);
        if (! $blob) {
            throw new NotFoundException();
        }

        $blob->SetCommit($co);
        $blob->SetPath($this->params['file']);
        $this->tpl->assign('blob', $blob);

        list($history, $hasmore) = $blob->GetPaginatedHistory(101, $this->params['page'] * 100);
        if ($hasmore) {
            $this->tpl->assign('hasmorerevs', true);
            $history = array_slice($history, 0, 100);
        }
        $commits_of_history = array_map(
            function (FileDiff $file_diff) {
                return $file_diff->GetCommit();
            },
            $history
        );

        $commit_metadata_retriever = new CommitMetadataRetriever(
            new CommitStatusRetriever(new CommitStatusDAO()),
            UserManager::instance()
        );
        $builder = new ShortlogPresenterBuilder($commit_metadata_retriever);
        $this->tpl->assign(
            'shortlog_presenter',
            $builder->getShortlogPresenter($this->getTuleapGitRepository(), ...$commits_of_history)
        );
    }
}
