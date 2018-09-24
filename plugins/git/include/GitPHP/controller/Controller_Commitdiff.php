<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class Controller_Commitdiff extends Controller_DiffBase // @codingStandardsIgnoreLine
{

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
        parent::__construct();
        if (!$this->project) {
            throw new MessageException(__('Project is required'), true);
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
        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return 'commitdiffplain.tpl';
        }
        if (\ForgeConfig::get('git_repository_bp')) {
            return 'tuleap/commit-diff.tpl';
        }
        return 'commitdiff.tpl';
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
            return __('commitdiff');
        }
        return 'commitdiff';
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

        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['hp'])) {
            $this->params['hashparent'] = $_GET['hp'];
        }
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() // @codingStandardsIgnoreLine
    {
        parent::LoadHeaders();

        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            $this->headers[] = 'Content-disposition: attachment; filename="git-' . $this->params['hash'] . '.patch"';
            $this->headers[] = 'X-Content-Type-Options: nosniff';
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

        if (isset($this->params['hashparent'])) {
            $this->tpl->assign("hashparent", $this->params['hashparent']);
        }

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $this->tpl->assign('sidebyside', true);
            $this->tpl->assign('extrascripts', array('commitdiff'));
        }

        $treediff = new TreeDiff(
            $this->project,
            $this->params['hash'],
            (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
        );
        if (\ForgeConfig::get('git_repository_bp')) {
            $commit_presenter = new CommitPresenter($commit, $treediff);
            $this->tpl->assign('commit_presenter', $commit_presenter);
        }
        $this->tpl->assign('treediff', $treediff);
    }
}
