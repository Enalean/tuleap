<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Statistics_ServicesUsageDao extends DataAccessObject {

    private $end_date;
    private $start_date;

    /**
     * Constructor
     *
     * @param DataAccess $da Data access details
     * @param String $start_date
     * @param String $end_date
     *
     * @return Statistics_DiskUsageDao
     */
    public function __construct(DataAccess $da, $start_date, $end_date) {
        parent::__construct($da);
        $this->start_date = strtotime($start_date);
        $this->end_date   = strtotime($end_date);

    }

    public function getNameOfActiveProjectsBeforeEndDate() {
        $sql = "SELECT group_id, group_name
            FROM groups
            WHERE status='A'
               AND register_time <= $this->end_date
            GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getDescriptionOfActiveProjectsBeforeEndDate() {
        $sql = "SELECT group_id, REPLACE(REPLACE (short_description, CHAR(13),' '),CHAR(10),' ')
                FROM groups
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getRegisterTimeOfActiveProjectsBeforeEndDate() {
        $sql = "SELECT group_id, FROM_UNIXTIME(register_time,'%Y-%m-%d')
                FROM groups
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getInfosFromTroveGroupLink() {
        $sql = "SELECT tgl.group_id, tc.shortname
                FROM trove_group_link tgl, trove_cat tc
                WHERE tgl.trove_cat_root='281'
                    AND tc.root_parent=tgl.trove_cat_root
                    AND tc.trove_cat_id=tgl.trove_cat_id
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getAdministrators() {
        $sql = "SELECT g.group_id, u.user_name
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getAdministratorsRealNames() {
        $sql = "SELECT g.group_id, u.realname
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getAdministratorsEMails() {
        $sql = "SELECT g.group_id, u.email
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getCVSActivities() {
        $sql = "SELECT group_id, SUM(cvs_commits)
                FROM group_cvs_full_history
                WHERE day <= $this->end_date
                    AND day >= $this->start_date
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getSVNActivities() {
        $sql = "SELECT group_id,COUNT(*)
                FROM  svn_commits
                WHERE date <= $this->end_date
                    AND date >= $this->start_date
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getGitActivities() {
        $sql = "SELECT project_id, count(*)
                FROM  plugin_git_log
                    INNER JOIN plugin_git USING(repository_id)
                WHERE push_date <= $this->end_date
                    AND push_date >= $this->start_date
                GROUP BY project_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getFilesPublished() {
        $sql = "SELECT p.group_id, COUNT(file_id )
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id= r.release_id
                    AND r.package_id= p.package_id
                    AND f.post_date <= $this->end_date
                    AND f.post_date >= $this->start_date
                GROUP BY p.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getDistinctFilesPublished() {
        $sql = "SELECT p.group_id, COUNT( DISTINCT file_id )
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND f.post_date <= $this->end_date
                GROUP BY p.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfDownloadedFilesBeforeEndDate() {
        $sql = "SELECT p.group_id, COUNT(filerelease_id )
                FROM filedownload_log l,frs_package p,frs_release r
                WHERE l.filerelease_id = r.release_id
                    AND r.package_id = p.package_id
                    AND l.time <= $this->end_date
                GROUP BY p.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfDownloadedFilesBetweenStartDateAndEndDate() {
        $sql = "SELECT p.group_id,SUM(downloads )
                FROM frs_dlstats_file_agg fdl, frs_file f,frs_package p,frs_release r
                WHERE fdl.file_id=f.file_id AND f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND fdl.day <= $this->end_date
                    AND fdl.day >= $this->start_date
                GROUP BY p.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfActiveMailingLists() {
        $sql = "SELECT group_id, COUNT( DISTINCT group_list_id )
                FROM mail_group_list
                WHERE is_public != 9
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfInactiveMailingLists() {
        $sql = "SELECT group_id, COUNT( DISTINCT group_list_id )
                FROM mail_group_list
                WHERE is_public = 9
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfActiveForums() {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public != 9
                GROUP BY  fg.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfInactiveForums() {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public = 9
                GROUP BY  fg.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getForumsActivities() {
        $sql = "SELECT group_id,COUNT(DISTINCT f.msg_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND f.date >= $this->start_date
                GROUP BY  fg.group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }

    public function getNumberOfWikiDocuments() {
        $sql = "SELECT group_id, COUNT( DISTINCT id)
                FROM wiki_group_list
                GROUP BY group_id";

        $return = array();
        $retrieve = $this->retrieve($sql);
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }
}

?>
