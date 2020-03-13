<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once __DIR__ . '/../../www/svn/svn_utils.php';

/**
 * The SVN log of a project.
 */
class SVN_LogFactory
{

    /**
     * @var Project
     */
    private $project;

    /**
     * Builds a new SVN_Log object representing the SVN log of the given
     * project.
     *
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Retrieves latests SVN revisions.
     *
     * @param string $limit  Limit results count (e.g. 50)
     * @param PFUser   $author Author to filter on (provide a user without name if you want no filtering)
     *
     * @return array
     */
    public function getRevisions($limit, PFUser $author)
    {
        $raw_revisions = $this->getRawRevisions($limit, $author);
        $revisions     = array();

        while ($raw_revision = db_fetch_array($raw_revisions)) {
            list($revision, $commit_id, $description, $date, $whoid) = $raw_revision;

            $revisions[] = array('revision' => $revision,
                                 'author'   => $whoid,
                                 'date'     => $date,
                                 'message'  => trim($description));
        }

        return $revisions;
    }

    /**
     * Returns the commiters on given period with their commit count
     *
     * @param int $start_date
     * @param int $end_date
     *
     * @return Array
     */
    public function getCommiters(TimeInterval $interval)
    {
        $stats = array();
        $dao   = $this->getDao();
        $dar   = $dao->searchCommiters($this->project->getID(), $interval);
        foreach ($dar as $row) {
            $stats[] = array('user_id' => $row['whoid'], 'commit_count' => $row['commit_count']);
        }
        return $stats;
    }

    public function getTopModifiedFiles(PFUser $user, TimeInterval $interval, $limit)
    {
        $where_forbidden = $this->getForbiddenPaths($user);

        $stats = array();
        $dao   = $this->getDao();
        $dar   = $dao->searchTopModifiedFiles($this->project->getID(), $interval, $limit, $where_forbidden);
        foreach ($dar as $row) {
            $stats[] = array('path' => $row['path'], 'commit_count' => $row['commit_count']);
        }
        return $stats;
    }

    /**
     * Return SVN path the user is not allowed to see
     *
     *
     * @return string
     */
    protected function getForbiddenPaths(PFUser $user)
    {
        $forbidden = svn_utils_get_forbidden_paths($user->getName(), $this->project->getSVNRootPath());
        $where_forbidden = "";
        foreach ($forbidden as $no_access => $v) {
            $where_forbidden .= " AND svn_dirs.dir not like '" . db_es(substr($no_access, 1)) . "%'";
        }
        return $where_forbidden;
    }

    /**
     * Same as getRawRevisionsAndCount(), but retrieves only the revisions,
     * without the revisions count.
     */
    private function getRawRevisions($limit, PFUser $author)
    {
        list($raw_revisions, $count) = $this->getRawRevisionsAndCount($limit, $author);
        return $raw_revisions;
    }

    /**
     * XXX: USE IN TESTS ONLY !!!
     *
     * Wraps svn_get_revisions for testing purpose.
     */
    public function getRawRevisionsAndCount($limit, PFUser $author)
    {
        return svn_get_revisions(
            $this->project,
            0,
            $limit,
            '',
            $author->getUserName(),
            '',
            '',
            0,
            false
        );
    }

    protected function getDao()
    {
        return new SVN_LogDao();
    }
}
