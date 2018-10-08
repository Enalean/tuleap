<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use UserManager;

/**
 * Search controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Search extends ControllerBase // @codingStandardsIgnoreLine
{
    use \Tuleap\Git\Repository\View\FeatureFlag;

    const SEARCH_COMMIT    = 'commit';
    const SEARCH_AUTHOR    = 'author';
    const SEARCH_COMMITTER = 'committer';

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @return controller
     */
    public function __construct()
    {
        if (! Config::GetInstance()->GetValue('search', true)) {
            throw new MessageException(dgettext("gitphp", 'Search has been disabled'), true);
        }

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
        if ($this->isTuleapBeauGitActivated()) {
            return 'tuleap/shortlog.tpl';
        }
        return 'search.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
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
        if (!isset($this->params['searchtype'])) {
            $this->params['searchtype'] = self::SEARCH_COMMIT;
        }

        if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
            throw new  MessageException(sprintf(dngettext("gitphp", 'You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2), 2), true);
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

        $results = array();
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
            if ($this->isTuleapBeauGitActivated()) {
                $this->tpl->assign('hasemptysearchresults', true);
            } else {
                throw new MessageException(sprintf(dgettext("gitphp", 'No matches for "%1$s"'), $this->params['search']), false);
            }
        }

        if (count($results) > 100) {
            if ($this->isTuleapBeauGitActivated()) {
                $this->tpl->assign('hasmorerevs', true);
            } else {
                $this->tpl->assign('hasmore', true);
            }
            $results = array_slice($results, 0, 100, true);
        }
        $this->tpl->assign('results', $results);

        $this->tpl->assign('page', $this->params['page']);

        if ($this->isTuleapBeauGitActivated()) {
            $builder = new ShortlogPresenterBuilder(UserManager::instance());
            $this->tpl->assign('shortlog_presenter', $builder->getShortlogPresenter($results));
        }
    }
}
