<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
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

use Tuleap\PHPWiki\WikiPage;

require_once __DIR__ . '/lib/WikiPage.class.php';
require_once __DIR__ . '/lib/Wiki.class.php';
require_once __DIR__ . '/views/WikiViews.class.php';

/**
 * Entry point of WikiService
 *
 * This class receive HTTP requests for Wiki Service.
 *
 */
class WikiService extends Controler
{
    public $wiki;

  /**
   * Constructor
   */
    public function __construct($id)
    {
        global $LANG, $is_wiki_page;

        //used so the search box will add the necessary element to the pop-up box
        $is_wiki_page = 1;

      /*
       * Check given id
       */
        $this->gid = (int) $id;

        if (empty($this->gid)) {
            exit_no_group();
        }

        $pm = ProjectManager::instance();
        $go = $pm->getProject($this->gid);
        if (!$go) {
            exit_no_group();
        }

        $this->wiki = new Wiki($this->gid);

      // Check access right
        $this->checkPermissions();

      // If Wiki for project doesn't exist, propose creation ... if user is project admin or wiki admin
        if (!$this->wiki->exist()) {
            if ((!user_ismember($this->gid, 'W2')) && (!user_ismember($this->gid, 'A'))) {
                exit_wiki_empty();
            }
        }

      // Set language for phpWiki
        if ($this->wiki->getLanguage_id()) {
            define('DEFAULT_LANGUAGE', $this->wiki->getLanguage_id());
            $LANG = $this->wiki->getLanguage_id();
        }
    }


  /**
   * Check access permissions for wiki and wiki pages.
   *
   * Check restriction for:
   *  wiki: whole wiki can be restricted.
   *  wikipage: each page of the wiki can be restricted.
   */
    public function checkPermissions()
    {
      // Check if user can access to whole wiki
        if (!$this->wiki->isAutorized(UserManager::instance()->getCurrentUser()->getId())) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText(
                    'wiki_wikiservice',
                    'acces_denied_whole',
                    session_make_url("/project/memberlist.php?group_id=" . $this->gid)
                ),
                CODENDI_PURIFIER_DISABLED
            );
            exit_permission_denied();
        }

      // Check if user can access to selected page
        if (!empty($_REQUEST['pagename'])) {
            $wp = new WikiPage($this->gid, $_REQUEST['pagename']);
            if (!$wp->isAutorized(UserManager::instance()->getCurrentUser()->getId())) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText(
                        'wiki_wikiservice',
                        'acces_denied_page',
                        session_make_url("/project/memberlist.php?group_id=" . $this->gid)
                    ),
                    CODENDI_PURIFIER_DISABLED
                );
                exit_permission_denied();
            }
        }
    }

  /**
   * Bind http request with views and actions
   */
    public function request()
    {
        if (!isset($this->view)) {
            $this->view = 'browse';
        }

        if (!empty($_REQUEST['pagename'])) {
            $this->view = 'empty';
        }

        if (isset($_REQUEST['format']) &&
         ($_REQUEST['format'] == 'rss')) {
            $this->view = 'empty';
        }

        if (isset($_REQUEST['pv']) && ($_REQUEST['pv'] == 1 || $_REQUEST['pv'] == 2)) {
            $this->view = 'empty';
        }

        if (isset($_REQUEST['action'])) {
            if ($_REQUEST['action'] == 'ziphtml') {
                $this->view = 'empty';
            }

            if ($_REQUEST['action'] == 'zip') {
                $this->view = 'empty';
            }

            if ($_REQUEST['action'] == 'add_temp_page') {
                $this->action = 'add_temp_page';
            }

            if ($_REQUEST['action'] == 'setWikiPagePerms') {
                $this->action = 'setWikiPagePerms';
            }
        }

        if (isset($_REQUEST['view']) &&
         ($_REQUEST['view'] == 'browsePages')) {
            $this->view = 'browsePages';
        }

      // If Wiki for project doesn't exist, propose creation...
        if (!$this->wiki->exist()) {
            if (! isset($_REQUEST['view']) || $_REQUEST['view'] != 'doinstall') {
                $this->view = 'install';
            } else {
                $this->view = 'doinstall';
            }
        }
    }
}
