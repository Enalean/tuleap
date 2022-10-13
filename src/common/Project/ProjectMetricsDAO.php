<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use Tuleap\DB\DataAccessObject;

class ProjectMetricsDAO extends DataAccessObject
{
    public function executeDailyRun(): void
    {
        $this->getDB()->run('DROP TABLE IF EXISTS project_counts_tmp');
        $this->getDB()->run('DROP TABLE IF EXISTS project_metric_tmp');
        $this->getDB()->run('DROP TABLE IF EXISTS project_metric_tmp1');

        // create a table to put the aggregates in
        $this->getDB()->run('CREATE TABLE project_counts_tmp (group_id int,type text,count float(8,5))');

        // forum messages
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT forum_group_list.group_id,'forum',log(3*count(forum.msg_id)) AS count
            FROM forum,forum_group_list
            WHERE forum.group_forum_id=forum_group_list.group_forum_id
            GROUP BY group_id");

        // Artifacts in trackers
        $table_exist = $this->getDB()->run('SHOW TABLES LIKE "artifact"');
        if (count($table_exist) > 0) {
            $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'artifacts',log(5*sum(artifact_id)) as count
            FROM artifact, artifact_group_list
            WHERE artifact.group_artifact_id=artifact_group_list.group_artifact_id
            GROUP BY group_id");
        }


        // svn low level accesses
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'svn',log(sum(svn_access_count)) AS count
            FROM group_svn_full_history
            GROUP BY group_id");

        // developers
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'developers',log(5*count(*)) AS count FROM user_group GROUP BY group_id");

        // file releases
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'filereleases',log(5*count(*))
            FROM filerelease
            GROUP BY group_id");

        // file downloads
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'downloads',log(.3*sum(downloads))
            FROM filerelease
            GROUP BY group_id");

        // news
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id,'news',log(10*count(id)) AS count
            FROM news_bytes
            WHERE is_approved <> 4
            GROUP BY group_id");

        // wiki access
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id, 'wiki', log(count(user_id)) AS count
            FROM wiki_log
            GROUP BY group_id");

        // docman items (without directories - item_type = 1)
        $this->getDB()->run("INSERT INTO project_counts_tmp
            SELECT group_id, 'docman', log(5*count(item_id)) AS count
            FROM plugin_docman_item
            WHERE item_type <> 1
            GROUP BY group_id");

        // create a new table to insert the final records into
        $this->getDB()->run("CREATE TABLE project_metric_tmp1 (ranking int not null primary key auto_increment,
            group_id int not null,
            value float (8,5))");

        $this->getDB()->run("INSERT INTO project_metric_tmp1 (group_id,value)
            SELECT project_counts_tmp.group_id,(sum(project_counts_tmp.count)) AS value
            FROM project_counts_tmp
            GROUP BY group_id ORDER BY value DESC");

        // numrows in the set
        $counts = $this->getDB()->single("SELECT count(*) FROM project_metric_tmp1");

        // create a new table to insert the final records into
        $this->getDB()->run("CREATE TABLE project_metric_tmp (ranking int not null primary key auto_increment,
            percentile float(8,2), group_id int not null)");

        $this->getDB()->run(
            "INSERT INTO project_metric_tmp (ranking,percentile,group_id)
            SELECT ranking,(100-(100*((ranking-1)/?))),group_id
            FROM project_metric_tmp1 ORDER BY ranking ASC",
            $counts
        );

        // create an index
        $this->getDB()->run("create index idx_project_metric_group on project_metric_tmp(group_id)");

        // drop the old metrics table
        $this->getDB()->run("DROP TABLE IF EXISTS project_metric");


        // move the new ratings to the correct table name
        $this->getDB()->run("alter table project_metric_tmp rename as project_metric");
    }
}
