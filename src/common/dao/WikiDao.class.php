<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2008
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

/**
 *  Data Access Object for wiki db access from other codendi components
 */
class WikiDao extends DataAccessObject
{
    /**
    * This function retreives an id from wiki_page table using the pagename attribute
    *
    * @param string $pagename
    * @param int $group_id
    * @return int $id id in wiki of a wiki page.
    */
    public function retrieveWikiPageId($pagename, $group_id)
    {
        $sql = sprintf(
            'SELECT id' .
            ' FROM wiki_page' .
            ' WHERE pagename = %s' .
            ' AND group_id = %d',
            $this->da->quoteSmart($pagename),
            $this->da->escapeInt($group_id)
        );
        $res = $this->retrieve($sql);
        if ($res && !$res->isError() && $res->rowCount() == 1) {
            $res->rewind();
            if ($res->valid()) {
                $row = $res->current();
                $id = $row['id'];
                return $id;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
    * Searches for the latest version of a wiki page
    *
    * @param int $groupId
    * @param string $pagename
    * @return int version number
    */
    public function searchCurrentWikiVersion($groupId, $pagename)
    {
        $version = null;
        $sql = sprintf(
            'SELECT MAX(version) AS version' .
                       ' FROM wiki_page ' .
                       '  INNER JOIN wiki_version USING(id)' .
                       ' WHERE group_id = %d' .
                       ' AND pagename = %s',
            $groupId,
            $this->da->quoteSmart($pagename)
        );
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $version = $row['version'];
        }
        return $version;
    }

    /**
     * Delete entry from wiki_page table identified by wiki page Id.
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    public function deleteWikiPage($id)
    {
        $sql = sprintf('DELETE FROM wiki_page' .
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete all entries from wiki_version table that refers to the same wiki page identified by  its Id
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    public function deleteWikiPageVersion($id)
    {
        $sql = sprintf('DELETE FROM wiki_version' .
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete links from and to wiki page identified by  its Id
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    public function deleteLinksFromToWikiPage($id)
    {
        $sql = sprintf('DELETE FROM wiki_link' .
                    ' WHERE linkfrom=%d' .
                    ' OR linkto=%d', $id, $id);
        return $this->update($sql);
    }

    /**
     * Delete wiki page identified by  its Id from non empty pages list
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    public function deleteWikiPageFromNonEmptyList($id)
    {
        $sql = sprintf('DELETE FROM wiki_nonempty' .
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Delete recent infos of wiki page identified by  its Id.
     *
     * @param int $id id of wiki page
     * @return true if there is no error
     */
    public function deleteWikiPageRecentInfos($id)
    {
        $sql = sprintf('DELETE FROM wiki_recent' .
                    ' WHERE id=%d', $id);
        return $this->update($sql);
    }

    /**
     * Update wiki page
     * @param PFUser   $user
     * @param String $new_name
     * @return bool
     */
    public function updatePageName($user, $new_name)
    {
        $sql = 'UPDATE wiki_page SET pagename = ' . $this->da->quoteSmart($new_name) .
               ' WHERE pagename = ' . $this->da->quoteSmart($user->getUserName());
        return $this->update($sql);
    }

    public function searchLanguage($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT DISTINCT wiki_group_list.language_id
                FROM wiki_group_list
                WHERE wiki_group_list.group_id=$group_id
                  AND wiki_group_list.language_id <> '0'";
        $dar = $this->retrieve($sql);
        if (count($dar) == 1) {
            $row = $dar->getRow();
            return $row['language_id'];
        }
        return false;
    }

    public function searchPaginatedUserWikiPages($project_id, $limit, $offset)
    {
        $admin_pages   = $this->da->quoteSmartImplode(',', WikiPage::getAdminPages());
        $default_pages = $this->da->quoteSmartImplode(',', WikiPage::getDefaultPages());

        $project_id = $this->da->escapeInt($project_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS wiki_page.pagename
                FROM wiki_page, wiki_nonempty
                WHERE wiki_page.group_id = $project_id
                    AND wiki_nonempty.id = wiki_page.id
                    AND wiki_page.pagename NOT IN ($default_pages,$admin_pages)
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchPaginatedUserWikiPagesByPagename($project_id, $limit, $offset, $pagename)
    {
        $admin_pages   = $this->da->quoteSmartImplode(',', WikiPage::getAdminPages());
        $default_pages = $this->da->quoteSmartImplode(',', WikiPage::getDefaultPages());

        $project_id = $this->da->escapeInt($project_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $pagename   = $this->da->quoteLikeValueSurround($pagename);

        $sql = "SELECT SQL_CALC_FOUND_ROWS wiki_page.pagename
                FROM wiki_page, wiki_nonempty
                WHERE wiki_page.group_id = $project_id
                    AND wiki_nonempty.id = wiki_page.id
                    AND wiki_page.pagename LIKE $pagename
                    AND wiki_page.pagename NOT IN ($default_pages,$admin_pages)
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function doesWikiPageExistInRESTContext($page_id)
    {
        $admin_pages   = $this->da->quoteSmartImplode(',', WikiPage::getAdminPages());
        $default_pages = $this->da->quoteSmartImplode(',', WikiPage::getDefaultPages());

        $page_id = $this->da->escapeInt($page_id);

        $sql = "SELECT SQL_CALC_FOUND_ROWS wiki_page.id
                FROM wiki_page, wiki_nonempty
                WHERE wiki_page.id = $page_id
                    AND wiki_nonempty.id <> wiki_page.id
                    AND wiki_page.pagename NOT IN ($default_pages,$admin_pages)";

        $this->retrieve($sql);

        return (int) $this->foundRows();
    }
}
