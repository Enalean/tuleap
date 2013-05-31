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

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getDescriptionOfActiveProjectsBeforeEndDate() {
        $sql = "SELECT group_id, REPLACE(REPLACE (short_description, CHAR(13),' '),CHAR(10),' ')
                FROM groups
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getRegisterTimeOfActiveProjectsBeforeEndDate() {
        $sql = "SELECT group_id, FROM_UNIXTIME(register_time,'%Y-%m-%d')
                FROM groups
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getInfosFromTroveGroupLink() {
        $sql = "SELECT tgl.group_id, tc.shortname
                FROM trove_group_link tgl, trove_cat tc
                WHERE tgl.trove_cat_root='281'
                    AND tc.root_parent=tgl.trove_cat_root
                    AND tc.trove_cat_id=tgl.trove_cat_id
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getAdministrators() {
        $sql = "SELECT g.group_id, u.user_name
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getAdministratorsRealNames() {
        $sql = "SELECT g.group_id, u.realname
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getAdministratorsEMails() {
        $sql = "SELECT g.group_id, u.email
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                    AND u.status='A'
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getCVSActivities() {
        $sql = "SELECT group_id, SUM(cvs_commits)
                FROM group_cvs_full_history
                WHERE day <= $this->end_date
                    AND day >= $this->start_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getSVNActivities() {
        $sql = "SELECT group_id,COUNT(*)
                FROM  svn_commits
                WHERE date <= $this->end_date
                    AND date >= $this->start_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getGitActivities() {
        $sql = "SELECT project_id, count(*)
                FROM  plugin_git_log
                    INNER JOIN plugin_git USING(repository_id)
                WHERE push_date <= $this->end_date
                    AND push_date >= $this->start_date
                GROUP BY project_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getFilesPublished() {
        $sql = "SELECT p.group_id, COUNT(file_id )
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id= r.release_id
                    AND r.package_id= p.package_id
                    AND f.post_date <= $this->end_date
                    AND f.post_date >= $this->start_date
                GROUP BY p.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getDistinctFilesPublished() {
        $sql = "SELECT p.group_id, COUNT( DISTINCT file_id )
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND f.post_date <= $this->end_date
                GROUP BY p.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfDownloadedFilesBeforeEndDate() {
        $sql = "SELECT p.group_id, COUNT(filerelease_id )
                FROM filedownload_log l,frs_package p,frs_release r
                WHERE l.filerelease_id = r.release_id
                    AND r.package_id = p.package_id
                    AND l.time <= $this->end_date
                GROUP BY p.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfDownloadedFilesBetweenStartDateAndEndDate() {
        $sql = "SELECT p.group_id,SUM(downloads )
                FROM frs_dlstats_file_agg fdl, frs_file f,frs_package p,frs_release r
                WHERE fdl.file_id=f.file_id AND f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND fdl.day <= $this->end_date
                    AND fdl.day >= $this->start_date
                GROUP BY p.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfActiveMailingLists() {
        $sql = "SELECT group_id, COUNT( DISTINCT group_list_id )
                FROM mail_group_list
                WHERE is_public != 9
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfInactiveMailingLists() {
        $sql = "SELECT group_id, COUNT( DISTINCT group_list_id )
                FROM mail_group_list
                WHERE is_public = 9
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfActiveForums() {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public != 9
                GROUP BY  fg.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfInactiveForums() {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public = 9
                GROUP BY  fg.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getForumsActivitiesBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id,COUNT(DISTINCT f.msg_id )
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND f.date >= $this->start_date
                GROUP BY  fg.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfWikiDocuments() {
        $sql = "SELECT group_id, COUNT( DISTINCT id)
                FROM wiki_group_list
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id, COUNT(pagename)
                FROM wiki_log
                WHERE time <= $this->end_date
                    AND time >= $this->start_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfDistinctWikiPages() {
        $sql = "SELECT group_id, COUNT( DISTINCT pagename)
                FROM wiki_log
                WHERE time <= $this->end_date
                GROUP BY group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfOpenArtifactsBetweenStartDateAndEndDate() {
        $sql = "SELECT artifact_group_list.group_id, COUNT(artifact.artifact_id)
                FROM artifact_group_list, artifact
                WHERE ( open_date >= $this->start_date
                    AND open_date < $this->end_date
                    AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
                GROUP BY artifact_group_list.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfClosedArtifactsBetweenStartDateAndEndDate() {
        $sql = "SELECT artifact_group_list.group_id, COUNT(artifact.artifact_id)
                FROM artifact_group_list, artifact
                WHERE ( close_date >= $this->start_date
                    AND close_date < $this->end_date
                    AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
                GROUP BY artifact_group_list.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfUserAddedBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id,COUNT(u.user_id)
                FROM user_group ug, user u
                WHERE u.user_id = ug.user_id
                    AND add_date >= $this->start_date
                    AND add_date <= $this->end_date
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getProjectCode() {
        $sql = "SELECT g.group_id, value
                FROM groups g,group_desc_value gdv, group_desc gd
                WHERE g.group_id = gdv.group_id
                    AND gdv.group_desc_id = gd.group_desc_id
                    AND gd.desc_name = 'Code projet'
                    AND register_time <= $this->end_date
                GROUP BY g.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getAddedDocumentBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id, COUNT(item_id)
                FROM plugin_docman_item
                WHERE create_date >= $this->start_date
                    AND create_date <= $this->end_date
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getDeletedDocumentBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id, COUNT(item_id)
                FROM plugin_docman_item
                WHERE delete_date >= $this->start_date
                    AND delete_date <= $this->end_date
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfNewsBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id, COUNT(id)
                FROM news_bytes
                WHERE date >= $this->start_date
                    AND date <= $this->end_date
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getActiveSurveys() {
        $sql = "SELECT g.group_id, COUNT(survey_id)
                FROM surveys s, groups g
                WHERE is_active = 1
                    AND g.group_id = s.group_id
                GROUP BY  g.group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getSurveysAnswersBetweenStartDateAndEndDate() {
        $sql = "SELECT group_id, COUNT(*)
                FROM survey_responses
                WHERE date >= $this->start_date
                    AND date <= $this->end_date
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getProjectWithCIActivated() {
        $sql = "SELECT group_id, is_used
                FROM service
                WHERE short_name = 'hudson'
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getNumberOfCIJobs() {
        $sql = "SELECT group_id, COUNT(*)
                FROM plugin_hudson_job
                GROUP BY  group_id";

        $retrieve = $this->retrieve($sql);
        return $this->processMultipleRowInResult($retrieve);
    }

    public function getMediawikiPagesNumberOfAProject(PFO_Project $project) {
        $database_name = "plugin_mediawiki_". $project->getUnixName();

        $sql = "USE $database_name;
                SELECT COUNT(page_id)
                FROM mwpage";

        $result = $this->retrieve($sql);
        $this->DataAccessObject();

        return $result;
    }

    private function processMultipleRowInResult(DataAccessResult $retrieve) {
        $return = array();
        foreach ($retrieve as $res) {
            $return[] = $res;
        }

        return $return;
    }
}

?>
