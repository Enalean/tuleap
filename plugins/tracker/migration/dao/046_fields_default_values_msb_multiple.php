<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require(getenv('CODENDI_LOCAL_INC') ? getenv('CODENDI_LOCAL_INC') : '/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);

function err($msg) {
    echo PHP_EOL. $msg .PHP_EOL;
    exit(1);
}

mysql_connect($sys_dbhost, $sys_dbuser, $sys_dbpasswd) or err(mysql_error());
mysql_select_db($sys_dbname) or err(mysql_error());


// multiple default value for static msb
$sql = "SELECT f.id, old.default_value 
        FROM tracker_field AS f
            INNER JOIN artifact_field AS old ON (
                f.old_id = old.field_id AND 
                f.tracker_id = old.group_artifact_id AND 
                f.formElement_type = 'msb' AND 
                (old.value_function IS NULL OR old.value_function = '') AND 
                POSITION(',' IN old.default_value) <> 0)";

$res = mysql_query($sql) or err(mysql_error());
if (mysql_num_rows($res)) {
    while($data = mysql_fetch_assoc($res)) {
        $sql_data = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                     SELECT " . $data['id'] . ", new.id 
                     FROM tracker_field_list_bind_static_value AS new 
                     WHERE new.field_id = " . $data['id'] . " AND
                           new.old_id IN (" . $data['default_value'] . ")";
        mysql_query($sql_data) or err(mysql_error());
    }
}


// multiple default value for users msb
$sql = "SELECT f.id, old.default_value 
        FROM tracker_field AS f
            INNER JOIN artifact_field AS old ON (
                f.old_id = old.field_id AND 
                f.tracker_id = old.group_artifact_id AND 
                f.formElement_type = 'msb' AND 
                (old.value_function IS NOT NULL AND old.value_function <> '') AND 
                POSITION(',' IN old.default_value) <> 0)";

$res = mysql_query($sql) or err(mysql_error());
if (mysql_num_rows($res)) {
    while($data = mysql_fetch_assoc($res)) {
        $sql_data = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                     SELECT " . $data['id'] . ", user_id 
                     FROM user 
                     WHERE user_id IN (" . $data['default_value'] . ") AND 
                           user_id <> 100";
        mysql_query($sql_data) or err(mysql_error());
    }
}

?>
