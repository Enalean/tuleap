<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

use Tuleap\PHPWiki\WikiPage;

require_once __DIR__ . '/../../../www/project/admin/permissions.php';
require_once __DIR__ . '/WikiPage.class.php';
/**
 * Manipulation of Wiki service.
 *
 * This class is a part of the Model of Wiki Service it aims to be the
 * interface between data corresponding to a Wiki Service (instance of
 * PhpWiki for Codendi) and Codendi application
 *
 */
class Wiki
{
    public $gid;
    public string $language_id = "";
    public ?bool $exist;

  /**
   * WikiSericeModel - Constructor
   *
   * @access public
   * @param  int $id Project identifier
   */
    public function __construct($id = 0)
    {
        $this->gid   = (int) $id;
        $this->exist = null;
    }

  /**
   *
   * @return bool Return if a permission is set on this Wiki
   */
    public function permissionExist()
    {
        return permission_exist('WIKI_READ', $this->gid);
    }

    /**
     * @param  int     User identifier
     *
     * @return bool
     */
    public function isAutorized($uid)
    {
        $user = UserManager::instance()->getUserById($uid);

        if ($user === null) {
            return false;
        }

        if (
            $user->isMember($this->gid, ProjectUGroup::PROJECT_ADMIN_PERMISSIONS)
            || $user->isMember($this->gid, ProjectUGroup::WIKI_ADMIN_PERMISSIONS)
        ) {
            return true;
        }

        return permission_is_authorized('WIKI_READ', $this->gid, $uid, $this->gid);
    }

  /**
   * Set access permissions.
   *
   * @param  string[] $groups List of groups allowed to access to the Wiki
   * @return bool Modification status
   */
    public function setPermissions($groups)
    {
        global $feedback;

        /** @psalm-suppress DeprecatedFunction */
        list ($ret, $feedback) = permission_process_selection_form(
            $this->gid,
            'WIKI_READ',
            $this->gid,
            $groups
        );
        return $ret;
    }

  /**
   * Reset access permissions.
   *
   * @return bool Modification status
   */
    public function resetPermissions()
    {
        return permission_clear_all(
            $this->gid,
            'WIKI_READ',
            $this->gid
        );
    }

  /**
   * Check WikiEntry existance for given project.
   * @return bool
   */
    public function exist()
    {
        if ($this->exist === null) {
            $res = db_query('SELECT count(*) AS nb FROM wiki_page'
                          . ' WHERE group_id=' . db_ei($this->gid));

            $this->exist = (db_result($res, 0, 'nb') > 0);
        }
        return $this->exist;
    }

  /**
   * Get number of wiki pages.
   * @return number of pages (0 if wiki is empty)
   */
    public function getPageCount()
    {
        $res = db_query(' SELECT count(*) as count'
        . ' FROM wiki_page, wiki_nonempty'
        . ' WHERE wiki_page.group_id="' . db_ei($this->gid) . '"'
        . ' AND wiki_nonempty.id=wiki_page.id');

        if (db_numrows($res) > 0) {
            return db_result($res, 0, 'count');
        } else {
            return 0;
        }
    }

  /**
   * Get number of project wiki pages.
   * @return number of project pages (0 if wiki is empty)
   */
    public function getProjectPageCount()
    {
        $excluded_pages_db_escaped = [];
        foreach (array_merge(WikiPage::getAdminPages(), WikiPage::getDefaultPages()) as $excluded_page) {
            $excluded_pages_db_escaped[] = '"' . db_es($excluded_page) . '"';
        }
        $res = db_query(' SELECT count(*) as count'
        . ' FROM wiki_page, wiki_nonempty'
        . ' WHERE wiki_page.group_id="' . db_ei($this->gid) . '"'
        . ' AND wiki_nonempty.id=wiki_page.id'
            . ' AND wiki_page.pagename NOT IN (' . implode(',', $excluded_pages_db_escaped) . ')');

        if (db_numrows($res) > 0) {
            return db_result($res, 0, 'count');
        } else {
            return 0;
        }
    }

  /**
   * Get wiki language (set at creation time)
   * return 0 if no wiki document exist
   */
    public function getLanguage_id()
    {
        // The language of the wiki is the language of all its wiki documents.
        if (! $this->language_id) {
            // We only support one language for all the wiki documents of a project.
            $wei = WikiEntry::getEntryIterator($this->gid);
            if ($wei->valid()) {
                $we                = $wei->current(); // get first element
                $this->language_id = $we->getLanguage_id();
            }
        }
        return $this->language_id;
    }


  /**
   * Experimental
   */

    public function dropLink($id)
    {
        $res = db_query('  DELETE FROM wiki_link'
        . ' WHERE linkfrom=' . db_ei($id)
        . ' OR linkto=' . db_ei($id));

        if (db_affected_rows($res) === 1) {
            return true;
        }
    }

    public function dropNonEmpty($id)
    {
        $res = db_query('  DELETE FROM wiki_nonempty'
        . ' WHERE id=' . db_ei($id));
    }

    public function dropRecent($id)
    {
        $res = db_query('  DELETE FROM wiki_recent'
        . ' WHERE id=' . db_ei($id));
    }

    public function dropVersion($id)
    {
        $res = db_query('  DELETE FROM wiki_version'
        . ' WHERE id=' . db_ei($id));
    }

    public function dropPage($id)
    {
        $res = db_query('  DELETE FROM wiki_page'
        . ' WHERE id=' . db_ei($id));
    }

    public function drop()
    {
      //TODO: Drop entries

      // PhpWiki
        $res = db_query('  SELECT id FROM wiki_page'
        . ' WHERE group_id=' . db_ei($this->gid));

        while ($row = db_fetch_array($res)) {
            $pid = $row['id'];

            // Link
            $this->dropLink($pid);

            // Non empty
            $this->dropNonEmpty($pid);

            // Recent
            $this->dropRecent($pid);

            // Version
            $this->dropVersion($pid);

            // Page
            $this->dropPage($pid);
        }
    }
}
