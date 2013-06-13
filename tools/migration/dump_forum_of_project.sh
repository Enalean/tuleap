#!/bin/sh

#
# Copyright (c) Enalean, 2013. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#

# Dump the forums messages of a given project
#
# Usage: tools/migration/dump_forum_of_project.sh <project_id>

project_id=$1
scriptdir=`dirname $0`

db_conf_file=/etc/codendi/conf/database.inc

read_db_conf() {
    echo "<?php echo \$$1; ?>" | php -d auto_prepend_file=$db_conf_file
}

dbhost=`read_db_conf sys_dbhost`
dbname=`read_db_conf sys_dbname`
dbuser=`read_db_conf sys_dbuser`
dbpasswd=`read_db_conf sys_dbpasswd`

mysqlcmd="mysql -u$dbuser -p$dbpasswd -h$dbhost $dbname"
mysqldumpcmd="mysqldump -u$dbuser -p$dbpasswd -h$dbhost $dbname --compact --no-create-info --complete-insert"

set_group_concat_max_len="SET SESSION group_concat_max_len = 134217728"

belongs_to_project="group_id=$project_id"

# Dump various tables
$mysqldumpcmd news_bytes --where "$belongs_to_project"
echo

$mysqldumpcmd forum_group_list --where "$belongs_to_project"
echo

group_forum_ids=`$mysqlcmd -e "$set_group_concat_max_len; \
                SELECT GROUP_CONCAT(group_forum_id) \
                FROM forum_group_list \
                WHERE $belongs_to_project" | tail -n 1`

$mysqldumpcmd forum_agg_msg_count --where "group_forum_id IN ($group_forum_ids)"
echo

$mysqldumpcmd forum --where "group_forum_id IN ($group_forum_ids)"
echo


thread_ids=`$mysqlcmd -e "$set_group_concat_max_len; \
              SELECT GROUP_CONCAT(thread_id) \
              FROM forum INNER JOIN forum_group_list USING(group_forum_id) \
              WHERE $belongs_to_project" | tail -n 1`

$mysqldumpcmd forum_thread_id --where "thread_id IN ($thread_ids)"
echo
