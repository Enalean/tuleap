<?php
/**
 * Copyright (c) Enalean, 2012-2015. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_Apache_ModMysql extends SVN_Apache {

    protected function getProjectAuthentication($row) {
        $conf  = "    AuthMYSQLEnable on\n";
        $conf .= $this->getCommonAuthentication($row['group_name']);
        $conf .= "    AuthMySQLUser " . ForgeConfig::get('sys_dbauth_user') . "\n";
        $conf .= "    AuthMySQLPassword " . ForgeConfig::get('sys_dbauth_passwd') . "\n";
        $conf .= "    AuthMySQLDB " . ForgeConfig::get('sys_dbname') . "\n";
        $conf .= "    AuthMySQLHost " . ForgeConfig::get('sys_dbhost') . "\n";
        $conf .= "    AuthMySQLUserTable \"user, user_group\"\n";
        $conf .= "    AuthMySQLNameField user.user_name\n";
        $conf .= "    AuthMySQLPasswordField user.unix_pw\n";
        $conf .= "    AuthMySQLUserCondition \"(user.status='A' or (user.status='R' AND user_group.user_id=user.user_id and user_group.group_id=".intval($row['group_id'])."))\"\n";
        return $conf;
    }
}
