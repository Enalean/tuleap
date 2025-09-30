<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use GitPHP\Commit\CommitUserPresenter;

/**
 * GitPHP Controller Tag
 *
 * Controller for displaying a tag
 *
 */
/**
 * Tag controller class
 *
 */
class Controller_Tag extends ControllerBase // phpcs:ignore
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->project) {
            throw new MessageException(dgettext('gitphp', 'Project is required'), true);
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
    #[\Override]
    protected function GetTemplate() // phpcs:ignore
    {
        return 'tuleap/tag.tpl';
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
    #[\Override]
    public function GetName($local = false) // phpcs:ignore
    {
        if ($local) {
            return dgettext('gitphp', 'tag');
        }
        return 'tag';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    #[\Override]
    protected function ReadQuery(): void // phpcs:ignore
    {
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    #[\Override]
    protected function LoadData(): void // phpcs:ignore
    {
        $head = $this->project->GetHeadCommit();
        if ($head === null) {
            throw new NotFoundException();
        }
        $this->tpl->assign('head', $head);

        $tag = $this->project->GetTag($this->params['hash']);
        if ($tag === null) {
            throw new NotFoundException();
        }

        $tagger = $tag->GetTagger() ?: '';
        preg_match('/(?P<name>.*)\s*<(?P<email>.*)>/', $tagger, $matches);

        $tagger_name  = $matches['name'] ?? '';
        $tagger_email = $matches['email'] ?? '';
        $user         = $tagger_email ? \UserManager::instance()->getUserByEmail($tagger_email) : null;

        $this->tpl->assign('author', CommitUserPresenter::buildFromTuleapUser($user));
        $this->tpl->assign('tagger_name', $tagger_name);
        $this->tpl->assign('purifier', \Codendi_HTMLPurifier::instance());

        $this->tpl->assign('tag', $tag);
        $html_purifier        = \Codendi_HTMLPurifier::instance();
        $project_id           = (int) $this->getTuleapGitRepository()->getProjectId();
        $tag_comment_purified = $html_purifier->purify(implode(PHP_EOL, $tag->GetComment()), CODENDI_PURIFIER_BASIC_NOBR, $project_id);
        $this->tpl->assign('tag_comment_purified', $tag_comment_purified);
    }
}
