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
 * Search controller class
 *
 */
class Controller_Search extends ControllerBase // @codingStandardsIgnoreLine
{
    public const SEARCH_COMMIT    = 'commit';
    public const SEARCH_AUTHOR    = 'author';
    public const SEARCH_COMMITTER = 'committer';

    public function __construct()
    {
        if (! Config::GetInstance()->GetValue('search', true)) {
            throw new MessageException(dgettext("gitphp", 'Search has been disabled'), true);
        }

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
        return 'tuleap/shortlog.tpl';
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
            return dgettext("gitphp", 'search');
        }
        return 'search';
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
        if (! isset($this->params['searchtype'])) {
            $this->params['searchtype'] = self::SEARCH_COMMIT;
        }

        if ((! isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
            throw new MessageException(sprintf(dngettext("gitphp", 'You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2), 2), true);
        }

        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        } else {
            $this->params['hashbase'] = 'HEAD';
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
        $this->tpl->assign('commit', $co);
        $this->tpl->assign('hashbase', $this->params['hashbase']);

        $results = [];
        if ($co) {
            switch ($this->params['searchtype']) {
                case self::SEARCH_COMMIT:
                    $results = $this->project->SearchCommit($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::SEARCH_AUTHOR:
                    $results = $this->project->SearchAuthor($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::SEARCH_COMMITTER:
                    $results = $this->project->SearchCommitter($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;
                default:
                    throw new MessageException(dgettext("gitphp", 'Invalid search type'), true);
            }

            $this->tpl->assign('tree', $co->GetTree());
        }

        if (count($results) < 1) {
            $this->tpl->assign('hasemptysearchresults', true);
        }

        if (count($results) > 100) {
            $this->tpl->assign('hasmorerevs', true);
            $results = array_slice($results, 0, 100, true);
        }
        $this->tpl->assign('results', $results);

        $this->tpl->assign('page', $this->params['page']);

        $commit_metadata_retriever = new CommitMetadataRetriever(
            new CommitStatusRetriever(new CommitStatusDAO()),
            UserManager::instance()
        );
        $builder = new ShortlogPresenterBuilder($commit_metadata_retriever);
        $this->tpl->assign(
            'shortlog_presenter',
            $builder->getShortlogPresenter($this->getTuleapGitRepository(), ...$results)
        );
    }
}
