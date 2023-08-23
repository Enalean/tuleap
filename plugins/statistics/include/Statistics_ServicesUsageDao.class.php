<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class Statistics_ServicesUsageDao extends DataAccessObject
{
    private $end_date;
    private $start_date;

    /**
     * Constructor
     *
     * @param LegacyDataAccessInterface $da Data access details
     * @param String $start_date
     * @param String $end_date
     *
     * @return Statistics_DiskUsageDao
     */
    public function __construct(LegacyDataAccessInterface $da, $start_date, $end_date)
    {
        parent::__construct($da);
        $this->start_date = strtotime($start_date);
        $this->end_date   = strtotime($end_date);
    }

    public function getNameOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, group_name AS result
            FROM `groups`
            WHERE status='A'
               AND register_time <= $this->end_date
            GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getShortNameOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, unix_group_name AS result
            FROM `groups`
            WHERE status='A'
               AND register_time <= $this->end_date
            GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getIdsOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, group_id AS result
            FROM `groups`
            WHERE status='A'
               AND register_time <= $this->end_date
            GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getPrivacyOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, access AS result
            FROM `groups`
            WHERE status='A'
               AND register_time <= $this->end_date
            GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getDescriptionOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, REPLACE(REPLACE (short_description, CHAR(13),' '),CHAR(10),' ') AS result
                FROM `groups`
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getRegisterTimeOfActiveProjectsBeforeEndDate()
    {
        $sql = "SELECT group_id, FROM_UNIXTIME(register_time,'%Y-%m-%d') AS result
                FROM `groups`
                WHERE status='A'
                    AND register_time <= $this->end_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getInfosFromTroveGroupLink()
    {
        $sql = "SELECT tgl.group_id, tc.shortname AS result
                FROM trove_group_link tgl, trove_cat tc
                WHERE tgl.trove_cat_root='281'
                    AND tc.root_parent=tgl.trove_cat_root
                    AND tc.trove_cat_id=tgl.trove_cat_id
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getAdministrators()
    {
        $sql = "SELECT g.group_id, u.user_name AS result
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getAdministratorsRealNames()
    {
        $sql = "SELECT g.group_id, u.realname AS result
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getAdministratorsEMails()
    {
        $sql = "SELECT g.group_id, u.email AS result
                FROM user_group g, user u
                WHERE g.user_id=u.user_id
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getBuiltFromTemplateIdBeforeEndDate()
    {
        $end_date = $this->da->escapeInt($this->end_date);

        $sql = "SELECT group_id, IF(xml.template_name IS NOT NULL, 0, built_from_template) AS result
                FROM `groups`
                    LEFT JOIN project_template_xml AS xml ON (xml.id = `groups`.group_id)
                WHERE status='A'
                  AND register_time <= $end_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getBuiltFromTemplateNameBeforeEndDate()
    {
        $end_date = $this->da->escapeInt($this->end_date);

        $sql = "SELECT project.group_id, IF(xml.template_name IS NOT NULL, xml.template_name, template.group_name) AS result
                FROM `groups` AS project
                    INNER JOIN  `groups` AS template ON (template.group_id = project.built_from_template)
                    LEFT JOIN project_template_xml AS xml ON (xml.id = project.group_id)
                WHERE project.status='A'
                  AND project.register_time <= $end_date
                GROUP BY project.group_id";

        return $this->retrieve($sql);
    }

    public function getCVSActivities()
    {
        $cvs_format_start_date = $this->formatDateForCVS($this->start_date);
        $cvs_format_end_date   = $this->formatDateForCVS($this->end_date);

        $sql = "SELECT group_id, SUM(cvs_commits) AS result
                FROM group_cvs_full_history
                WHERE day <= $cvs_format_end_date
                    AND day >= $cvs_format_start_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    private function formatDateForCVS($timestamp)
    {
        return date("Ymd", $timestamp);
    }

    public function getSVNActivities()
    {
        $sql = "SELECT group_id,COUNT(*) AS result
                FROM  svn_commits
                WHERE date <= $this->end_date
                    AND date >= $this->start_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getGitRead()
    {
        $start_date = new DateTime('@' . $this->start_date);
        $end_date   = new DateTime('@' . $this->end_date);
        $start_day  = $start_date->format('Ymd');
        $end_day    = $end_date->format('Ymd');
        $sql        = "SELECT project_id AS group_id, SUM(git_read) AS result
                FROM  plugin_git_log_read_daily
                    INNER JOIN plugin_git USING(repository_id)
                WHERE day <= $end_day
                    AND day >= $start_day
                GROUP BY project_id";

        return $this->retrieve($sql);
    }

    public function getGitWrite()
    {
        $sql = "SELECT project_id AS group_id, count(*) AS result
                FROM  plugin_git_log
                    INNER JOIN plugin_git USING(repository_id)
                WHERE push_date <= $this->end_date
                    AND push_date >= $this->start_date
                GROUP BY project_id";

        return $this->retrieve($sql);
    }

    public function getFilesPublished()
    {
        $sql = "SELECT p.group_id, COUNT(file_id ) AS result
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id= r.release_id
                    AND r.package_id= p.package_id
                    AND f.post_date <= $this->end_date
                    AND f.post_date >= $this->start_date
                GROUP BY p.group_id";

        return $this->retrieve($sql);
    }

    public function getDistinctFilesPublished()
    {
        $sql = "SELECT p.group_id, COUNT( DISTINCT file_id ) AS result
                FROM frs_file f,frs_package p,frs_release r
                WHERE f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND f.post_date <= $this->end_date
                GROUP BY p.group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfDownloadedFilesBeforeEndDate()
    {
        $sql = "SELECT p.group_id, COUNT(filerelease_id ) AS result
                FROM filedownload_log l,frs_package p,frs_release r
                WHERE l.filerelease_id = r.release_id
                    AND r.package_id = p.package_id
                    AND l.time <= $this->end_date
                GROUP BY p.group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfDownloadedFilesBetweenStartDateAndEndDate()
    {
        $sql = "SELECT p.group_id,SUM(downloads ) AS result
                FROM frs_dlstats_file_agg fdl, frs_file f,frs_package p,frs_release r
                WHERE fdl.file_id=f.file_id AND f.release_id = r.release_id
                    AND r.package_id = p.package_id
                    AND fdl.day <= $this->end_date
                    AND fdl.day >= $this->start_date
                GROUP BY p.group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfActiveForums()
    {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id ) AS result
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public != 9
                GROUP BY  fg.group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfInactiveForums()
    {
        $sql = "SELECT group_id,COUNT( DISTINCT fg.group_forum_id ) AS result
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND fg.is_public = 9
                GROUP BY  fg.group_id";

        return $this->retrieve($sql);
    }

    public function getForumsActivitiesBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id,COUNT(DISTINCT f.msg_id ) AS result
                FROM forum_group_list fg, forum f
                WHERE fg.group_forum_id =f.group_forum_id
                    AND f.date <= $this->end_date
                    AND f.date >= $this->start_date
                GROUP BY  fg.group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfWikiDocuments()
    {
        $sql = "SELECT group_id, COUNT( DISTINCT id) AS result
                FROM wiki_group_list
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id, COUNT(pagename) AS result
                FROM wiki_log
                WHERE time <= $this->end_date
                    AND time >= $this->start_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfDistinctWikiPages()
    {
        $sql = "SELECT group_id, COUNT( DISTINCT pagename) AS result
                FROM wiki_log
                WHERE time <= $this->end_date
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfOpenArtifactsBetweenStartDateAndEndDate()
    {
        if (TrackerV3::instance()->available()) {
            $sql = "SELECT artifact_group_list.group_id, COUNT(artifact.artifact_id) AS result
                    FROM artifact_group_list, artifact
                    WHERE ( open_date >= $this->start_date
                        AND open_date < $this->end_date
                        AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
                    GROUP BY artifact_group_list.group_id";
            return $this->retrieve($sql);
        } else {
            return [];
        }
    }

    public function getNumberOfClosedArtifactsBetweenStartDateAndEndDate()
    {
        if (TrackerV3::instance()->available()) {
            $sql = "SELECT artifact_group_list.group_id, COUNT(artifact.artifact_id) AS result
                    FROM artifact_group_list, artifact
                    WHERE ( close_date >= $this->start_date
                        AND close_date < $this->end_date
                        AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
                    GROUP BY artifact_group_list.group_id";
            return $this->retrieve($sql);
        } else {
            return [];
        }
    }

    public function getNumberOfUserAddedBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id,COUNT(u.user_id) AS result
                FROM user_group ug, user u
                WHERE u.user_id = ug.user_id
                    AND add_date >= $this->start_date
                    AND add_date <= $this->end_date
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }

    public function getAddedDocumentBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id, COUNT(item_id) AS result
                FROM plugin_docman_item
                WHERE create_date >= $this->start_date
                    AND create_date <= $this->end_date
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }

    public function getDeletedDocumentBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id, COUNT(item_id) AS result
                FROM plugin_docman_item
                WHERE delete_date >= $this->start_date
                    AND delete_date <= $this->end_date
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfNewsBetweenStartDateAndEndDate()
    {
        $sql = "SELECT group_id, COUNT(id) AS result
                FROM news_bytes
                WHERE date >= $this->start_date
                    AND date <= $this->end_date
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }

    public function getProjectWithCIActivated()
    {
        $sql = "SELECT group_id, is_used AS result
                FROM service
                WHERE short_name = 'hudson'
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }

    public function getNumberOfCIJobs()
    {
        $sql = "SELECT group_id, COUNT(*) AS result
                FROM plugin_hudson_job
                GROUP BY  group_id";

        return $this->retrieve($sql);
    }
}
